<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Ticket;
use Pterodactyl\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TicketProController extends ClientApiController
{
    /**
     * Set typing status for the user.
     */
    public function typing(ClientApiRequest $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id && !$request->user()->root_admin) {
            abort(403, 'Unauthorized');
        }

        $cacheKey = "ticketpro_typing_{$ticket->id}";
        $typists = Cache::get($cacheKey, []);
        
        $typists[$request->user()->id] = [
            'name' => $request->user()->username,
            'expires' => time() + 5
        ];

        foreach ($typists as $id => $data) {
            if ($data['expires'] < time()) {
                unset($typists[$id]);
            }
        }

        Cache::put($cacheKey, $typists, 10);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Get all tickets for the authenticated user.
     */

    /**
     * Get unread ticket count for the authenticated user.
     */
    public function unread(ClientApiRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $unreadCount = \Illuminate\Support\Facades\DB::table('tickets')
            ->where('status', 'open')
            ->where('user_id', $userId)
            ->whereRaw('(SELECT user_id FROM ticket_messages WHERE ticket_messages.ticket_id = tickets.id ORDER BY created_at DESC LIMIT 1) != ?', [$userId])
            ->count();

        return new JsonResponse([
            'success' => true,
            'unread' => $unreadCount > 0
        ]);
    }

    public function index(ClientApiRequest $request): JsonResponse
    {
        $tickets = Ticket::where('user_id', $request->user()->id)->orderBy('created_at', 'desc')->get();
        $servers = $request->user()->servers()->get(['id', 'name', 'uuidShort']);
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'tickets' => $tickets,
                'servers' => $servers
            ]
        ]);
    }

    /**
     * Create a new ticket.
     */
    public function store(ClientApiRequest $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:50',
            'vps_access_grant' => 'nullable',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf,txt|max:10240',
            'server_id' => 'nullable|exists:servers,id',
            'message' => 'required|string',
        ], [
            'message.required' => 'Pesan tidak boleh kosong.',
        ]);

        $messageText = $request->input('message');

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('ticket_images', 'public');
        }

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'server_id' => $request->input('server_id'),
            'department' => $request->input('department', 'Technical Support'),
            'priority' => $request->input('priority', 'Medium'),
            'vps_access_grant' => filter_var($request->input('vps_access_grant'), FILTER_VALIDATE_BOOLEAN),
            'title' => $request->input('title'),
            'status' => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $messageText,
            'image_path' => $imagePath,
        ]);

        // Send Discord Webhook Notification
        try {
            $webhookUrl = config('services.discord.ticket_webhook', env('DISCORD_TICKET_WEBHOOK_URL'));
            if (!$webhookUrl) {
                // Fallback to general deploy / alert webhook
                $webhookUrl = env('DISCORD_WEBHOOK_URL');
            }
            if ($webhookUrl) {
                $serverInfo = $ticket->server ? "{$ticket->server->name} ({$ticket->server->uuidShort})" : 'Tidak Ada Server';
                $vpsGrant = $ticket->vps_access_grant ? '✅ DIIZINKAN (Granted)' : '❌ TIDAK DIIZINKAN';
                
                Http::post($webhookUrl, [
                    'embeds' => [
                        [
                            'title' => "🎫 Support Ticket Baru: #{$ticket->id} - {$ticket->title}",
                            'url' => config('app.url') . "/admin/extensions/ticketpro/{$ticket->id}",
                            'color' => 0x3498db,
                            'fields' => [
                                ['name' => '👤 User', 'value' => "{$request->user()->username} ({$request->user()->email})", 'inline' => true],
                                ['name' => '🏢 Department', 'value' => $ticket->department, 'inline' => true],
                                ['name' => '⚡ Priority', 'value' => $ticket->priority, 'inline' => true],
                                ['name' => '🖥️ Server Terkait', 'value' => $serverInfo, 'inline' => true],
                                ['name' => '🔑 Akses VPS/Agent', 'value' => $vpsGrant, 'inline' => true],
                                ['name' => '💬 Pesan Pertama', 'value' => mb_strimwidth($messageText, 0, 1000, '...'), 'inline' => false],
                            ],
                            'footer' => ['text' => 'ByteNodes TicketPro Notification'],
                            'timestamp' => date('c'),
                        ]
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('TicketPro Discord webhook error: ' . $e->getMessage());
        }

        return new JsonResponse([
            'success' => true,
            'data' => $ticket
        ]);
    }

    /**
     * Get ticket details and messages.
     */
    public function show(ClientApiRequest $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id && !$request->user()->root_admin) {
            abort(403, 'Unauthorized');
        }

        $ticket->load(['user', 'server']);
        $messages = TicketMessage::with('user')->where('ticket_id', $ticket->id)->orderBy('created_at', 'asc')->get();

        // Roles to display (ordered by priority - highest first)
        $displayRoles = [
            '1446006920514375833' => ['name' => 'Founder',                  'color' => '#FFD700'],
            '1445078072868081852' => ['name' => 'Owner',                    'color' => '#FF2244'],
            '1445078072868081850' => ['name' => 'Chief Executive Officer',  'color' => '#F1C40F'],
            '1445078072868081849' => ['name' => 'Chief Operating Officer',  'color' => '#2077B0'],
            '1445078072830197849' => ['name' => 'Chief Technology Officer', 'color' => '#2ECC71'],
            '1445078072868081848' => ['name' => 'Manager',                  'color' => '#7EC850'],
            '1445078072830197847' => ['name' => 'Developer',                'color' => '#E74C3C'],
            '1445078072830197848' => ['name' => 'Contributors',             'color' => '#ACB26B'],
            '1445078072830197846' => ['name' => 'Moderator',                'color' => '#3498DB'],
            '1445078072830197845' => ['name' => 'Staff',                    'color' => '#1AB8A8'],
        ];
        $guildId = env('DISCORD_GUILD_ID') ?: '1445078072414965985';

        $messages->transform(function ($message) use ($displayRoles, $guildId) {
            if ($message->user && $message->user->discord_id) {
                $discordId = $message->user->discord_id;
                $cacheKey = "discord_profile_{$discordId}";
                
                $profile = \Illuminate\Support\Facades\Cache::get($cacheKey);
                
                if (!$profile) {
                    // Read bot token from database settings first, then .env
                    $settings = app(\Pterodactyl\Contracts\Repository\SettingsRepositoryInterface::class);
                    $token = $settings->get('settings::bytenodes::discord:bot_token');
                    if (!$token && file_exists(base_path('.env'))) {
                        $envContent = file_get_contents(base_path('.env'));
                        if (preg_match('/DISCORD_BOT_TOKEN="?([^"\n]+)"?/', $envContent, $matches)) {
                            $token = trim($matches[1]);
                        }
                    }
                    if (!$token) {
                        $token = env('DISCORD_BOT_TOKEN');
                    }
                    
                    if ($token) {
                        try {
                            // Fetch guild member (includes roles + user data)
                            $res = \Illuminate\Support\Facades\Http::withHeaders([
                                'Authorization' => "Bot {$token}",
                            ])->timeout(5)->get("https://discord.com/api/v10/guilds/{$guildId}/members/{$discordId}");
                            
                            if ($res->successful()) {
                                $data = $res->json();
                                $user = $data['user'] ?? [];
                                $memberRoleIds = $data['roles'] ?? [];
                                $avatarHash = $user['avatar'] ?? null;

                                // Find highest priority display role
                                $topRole = null;
                                foreach ($displayRoles as $roleId => $roleInfo) {
                                    if (in_array($roleId, $memberRoleIds)) {
                                        $topRole = $roleInfo;
                                        break;
                                    }
                                }

                                $profile = [
                                    'username'    => $data['nick'] ?? $user['global_name'] ?? $user['username'] ?? null,
                                    'discord_tag' => $user['username'] ?? null,
                                    'avatar'      => $avatarHash
                                        ? "https://cdn.discordapp.com/avatars/{$discordId}/{$avatarHash}.png?size=128"
                                        : "https://cdn.discordapp.com/embed/avatars/" . (intval($discordId) % 5) . ".png",
                                    'top_role'    => $topRole,
                                ];
                                \Illuminate\Support\Facades\Cache::put($cacheKey, $profile, 3600);
                            } else {
                                // Fallback: fetch user only
                                $res2 = \Illuminate\Support\Facades\Http::withHeaders([
                                    'Authorization' => "Bot {$token}",
                                ])->timeout(5)->get("https://discord.com/api/v10/users/{$discordId}");
                                if ($res2->successful()) {
                                    $u = $res2->json();
                                    $profile = [
                                        'username'    => $u['global_name'] ?? $u['username'] ?? null,
                                        'discord_tag' => $u['username'] ?? null,
                                        'avatar'      => isset($u['avatar'])
                                            ? "https://cdn.discordapp.com/avatars/{$discordId}/{$u['avatar']}.png?size=128"
                                            : null,
                                        'top_role'    => null,
                                    ];
                                    \Illuminate\Support\Facades\Cache::put($cacheKey, $profile, 3600);
                                }
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Discord Profile Fetch Error: " . $e->getMessage());
                        }
                    }
                }
                
                $message->discord_profile = $profile ?: null;
            }
            return $message;
        });



        $cacheKey = "ticketpro_typing_{$ticket->id}";
        $typists = Cache::get($cacheKey, []);
        $typingNames = [];
        foreach ($typists as $userId => $data) {
            if ($userId != $request->user()->id && $data['expires'] >= time()) {
                $typingNames[] = $data['name'];
            }
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'ticket' => $ticket,
                'messages' => $messages,
                'typing' => $typingNames
            ]
        ]);
    }

    /**
     * Reply to a ticket.
     */
    public function reply(ClientApiRequest $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id && !$request->user()->root_admin) {
            abort(403, 'Unauthorized');
        }

        if ($ticket->status === 'closed') {
            return new JsonResponse(['success' => false, 'message' => 'Ticket ini sudah ditutup.'], 400);
        }

        $request->validate([
            'message' => 'required|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('ticket_images', 'public');
        }

        $msg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
            'image_path' => $imagePath,
        ]);

        $msg->load('user');

        return new JsonResponse([
            'success' => true,
            'data' => $msg
        ]);
    }

    /**
     * Close a ticket/support session.
     */
    public function close(ClientApiRequest $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id && !$request->user()->root_admin) {
            abort(403, 'Unauthorized');
        }

        $ticket->update(['status' => 'closed']);

        // Clean up attached images if any
        $messagesWithImages = $ticket->messages()->whereNotNull('image_path')->get();
        foreach ($messagesWithImages as $msg) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($msg->image_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($msg->image_path);
            }
            $msg->update(['image_path' => null]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Support session closed successfully.'
        ]);
    }
}
