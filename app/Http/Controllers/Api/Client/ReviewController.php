<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ClientReview;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ReviewController extends Controller
{
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {}

    /**
     * Get review status for the authenticated user and public reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's servers
        $servers = Server::where('owner_id', $user->id)
            ->select('id', 'uuid', 'uuidShort', 'name')
            ->get();

        // Get user's submitted reviews
        $myReviews = ClientReview::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Check if user has active servers
        $hasActiveServer = $servers->isNotEmpty() || (bool) $user->root_admin;

        return response()->json([
            'success' => true,
            'data' => [
                'has_active_server' => $hasActiveServer,
                'servers' => $servers,
                'my_reviews' => $myReviews,
                'total_my_reviews' => $myReviews->count(),
            ],
        ]);
    }

    /**
     * Submit a new feedback/review from client.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:2000',
            'title' => 'nullable|string|max:120',
            'server_id' => 'nullable|string|max:64',
            'is_public' => 'nullable|boolean',
        ]);

        $serverName = null;
        if (!empty($validated['server_id'])) {
            $server = Server::where('uuidShort', $validated['server_id'])
                ->orWhere('uuid', $validated['server_id'])
                ->first();

            if ($server) {
                $serverName = $server->name;
            }
        }

        $review = ClientReview::create([
            'user_id' => $user->id,
            'server_id' => $validated['server_id'] ?? null,
            'server_name' => $serverName,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => trim($validated['comment']),
            'is_public' => $request->boolean('is_public', true),
            'is_approved' => true,
        ]);

        // Post review to Discord Webhook
        $this->sendDiscordWebhook($review, $user);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas ulasan Anda! Feedback Anda sangat berarti bagi pengembangan layanan ByteNodes.',
            'data' => $review,
        ]);
    }

    /**
     * Send rich embed review to Discord Webhook.
     */
    private function sendDiscordWebhook(ClientReview $review, $user): void
    {
        try {
            $webhookUrl = $this->settings->get('settings::bytenodes::discord:review_webhook_url')
                ?: env('DISCORD_REVIEW_WEBHOOK_URL')
                ?: $this->settings->get('settings::bytenodes::discord_webhooks::testimonial_url')
                ?: $this->settings->get('settings::bytenodes::discord_webhooks::url', 'https://discord.com/api/webhooks/1457425325590974497/dc0AOX65QwEOjmm4-lrNhCPXV1jmZsA6hGGXfJpoBq1cQJDIrlYcMgPGLojZ59DJsI5_');

            if (empty($webhookUrl)) {
                return;
            }

            $stars = str_repeat('★', $review->rating) . str_repeat('☆', 5 - $review->rating);
            $color = match ($review->rating) {
                5 => 0x2ECC71,
                4 => 0x3498DB,
                3 => 0xF39C12,
                default => 0xE74C3C,
            };

            $authorName = $user->username;
            if (!empty($user->name_first)) {
                $authorName = trim($user->name_first . ' ' . ($user->name_last ?? ''));
            }

            // Clean blockquote formatting for human review
            $cleanComment = trim($review->comment);
            $quotedComment = implode("\n", array_map(function ($line) {
                return '> ' . $line;
            }, explode("\n", $cleanComment)));

            $fields = [
                [
                    'name' => 'Rating',
                    'value' => "{$stars} ({$review->rating}/5)",
                    'inline' => true,
                ],
                [
                    'name' => 'Klien',
                    'value' => $user->discord_id ? "<@{$user->discord_id}>" : "`@{$user->username}`",
                    'inline' => true,
                ],
            ];

            if (!empty($review->server_name)) {
                $fields[] = [
                    'name' => 'Layanan',
                    'value' => "`{$review->server_name}`",
                    'inline' => true,
                ];
            }

            $avatarUrl = $user->discord_avatar ?: 'https://bytenodes.id/favicon.ico';
            $embedTitle = !empty($review->title) ? $review->title : "Testimoni dari {$authorName}";

            $payload = [
                'username' => 'ByteNodes Testimonial',
                'avatar_url' => 'https://bytenodes.id/favicon.ico',
                'embeds' => [
                    [
                        'title' => $embedTitle,
                        'description' => $quotedComment,
                        'color' => $color,
                        'fields' => $fields,
                        'footer' => [
                            'text' => 'ByteNodes Client Area • panel.bytenodes.id',
                            'icon_url' => 'https://bytenodes.id/favicon.ico',
                        ],
                        'timestamp' => now()->toIso8601String(),
                        'author' => [
                            'name' => $authorName,
                            'icon_url' => $avatarUrl,
                        ],
                    ],
                ],
            ];

            Http::timeout(5)->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            Log::warning('Failed to post review to Discord Webhook: ' . $e->getMessage());
        }
    }
}
