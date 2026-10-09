<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Exception;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class IntegrationsController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings
    ) {}

    public function index(): View
    {
        return view('admin.settings.integrations', [
            // AI Gateway & Diagnostics
            'ai_enabled' => (bool) $this->settings->get('settings::bytenodes::ai:enabled', true),
            'ai_base_url' => $this->settings->get('settings::bytenodes::ai:base_url', env('NINEROUTER_BASE_URL', 'https://routernines.bytenodes.icu/v1')),
            'ai_api_key' => $this->settings->get('settings::bytenodes::ai:api_key', env('NINEROUTER_API_KEY', 'sk-1e6c6b93a4e6c14d-podsu7-f1e560e2')),
            'ai_model' => $this->settings->get('settings::bytenodes::ai:model', env('NINEROUTER_MODEL', 'ag/gemini-3.8-flash')),
            'ai_max_attempts' => (int) $this->settings->get('settings::bytenodes::ai:max_attempts', 10),
            'ai_window_hours' => (int) $this->settings->get('settings::bytenodes::ai:window_hours', 72),

            // Discord Bot & Role
            'discord_bot_token' => $this->settings->get('settings::bytenodes::discord:bot_token', env('DISCORD_BOT_TOKEN', '')),
            'discord_guild_id' => $this->settings->get('settings::bytenodes::discord:guild_id', env('DISCORD_GUILD_ID', '1445078072414965985')),
            'discord_role_id' => $this->settings->get('settings::bytenodes::discord:role_id', env('DISCORD_ROLE_ID', '1445078072830197841')),

            // Discord OAuth2 Login
            'discord_client_id' => $this->settings->get('settings::bytenodes::discord:client_id', env('DISCORD_CLIENT_ID', '1484124938708324363')),
            'discord_client_secret' => $this->settings->get('settings::bytenodes::discord:client_secret', env('DISCORD_CLIENT_SECRET', 'UmyDOXTgCLN0iNblOlAD6Bf_SaFMxaE-')),
            'discord_redirect_url' => url('/auth/discord/callback'),

            // Discord Webhooks
            'discord_activity_webhook' => $this->settings->get('settings::bytenodes::discord_webhooks::url', env('DISCORD_WEBHOOK_URL', '')),
            'discord_review_webhook' => $this->settings->get('settings::bytenodes::discord:review_webhook_url', env('DISCORD_REVIEW_WEBHOOK_URL', 'https://discord.com/api/webhooks/1457425325590974497/dc0AOX65QwEOjmm4-lrNhCPXV1jmZsA6hGGXfJpoBq1cQJDIrlYcMgPGLojZ59DJsI5_')),
            'discord_deploy_webhook' => $this->settings->get('settings::bytenodes::deploy_discord_webhook', env('DEPLOY_DISCORD_WEBHOOK', 'https://discord.com/api/webhooks/1532646717214691349/2f-C1rfeB5BG52lbpX_bxD90a91ZZIuzCa9TYz6lNXH2-xea0FmcC_Kb4D22PUrCKYqH')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // 1. AI Settings
        $this->settings->set('settings::bytenodes::ai:enabled', $request->has('ai_enabled') ? '1' : '0');
        $this->settings->set('settings::bytenodes::ai:base_url', rtrim($request->input('ai_base_url', 'https://routernines.bytenodes.icu/v1'), '/'));
        $this->settings->set('settings::bytenodes::ai:api_key', trim($request->input('ai_api_key', '')));
        $this->settings->set('settings::bytenodes::ai:model', trim($request->input('ai_model', 'ag/gemini-3.8-flash')));
        $this->settings->set('settings::bytenodes::ai:max_attempts', max(1, (int) $request->input('ai_max_attempts', 10)));
        $this->settings->set('settings::bytenodes::ai:window_hours', max(1, (int) $request->input('ai_window_hours', 72)));

        // 2. Discord Bot & Role Settings
        $this->settings->set('settings::bytenodes::discord:bot_token', trim($request->input('discord_bot_token', '')));
        $this->settings->set('settings::bytenodes::discord:guild_id', trim($request->input('discord_guild_id', '')));
        $this->settings->set('settings::bytenodes::discord:role_id', trim($request->input('discord_role_id', '')));

        // 3. Discord OAuth2 Login Settings
        $this->settings->set('settings::bytenodes::discord:client_id', trim($request->input('discord_client_id', '')));
        $this->settings->set('settings::bytenodes::discord:client_secret', trim($request->input('discord_client_secret', '')));

        // 4. Discord Webhooks
        if ($request->has('discord_activity_webhook')) {
            $this->settings->set('settings::bytenodes::discord_webhooks::url', trim($request->input('discord_activity_webhook', '')));
        }
        $this->settings->set('settings::bytenodes::discord:review_webhook_url', trim($request->input('discord_review_webhook', '')));
        $this->settings->set('settings::bytenodes::deploy_discord_webhook', trim($request->input('discord_deploy_webhook', '')));
        $this->settings->set('settings::bytenodes:deploy_discord_webhook', trim($request->input('discord_deploy_webhook', '')));

        $this->alert->success('Pengaturan API & Integrasi berhasil disimpan dan diperbarui!')->flash();

        return redirect()->route('admin.settings.integrations');
    }

    public function testAi(Request $request): JsonResponse
    {
        $baseUrl = rtrim($request->input('base_url') ?: $this->settings->get('settings::bytenodes::ai:base_url', env('NINEROUTER_BASE_URL', 'https://routernines.bytenodes.icu/v1')), '/');
        $apiKey = trim($request->input('api_key') ?: $this->settings->get('settings::bytenodes::ai:api_key', env('NINEROUTER_API_KEY', 'sk-1e6c6b93a4e6c14d-podsu7-f1e560e2')));
        $model = trim($request->input('model') ?: $this->settings->get('settings::bytenodes::ai:model', env('NINEROUTER_MODEL', 'ag/gemini-3.8-flash')));

        $startTime = microtime(true);
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Ping! Return "PONG: ByteNodes AI Ready" only.'],
                ],
                'max_tokens' => 30,
            ]);

            $latency = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'OK (No content)';
                return response()->json([
                    'success' => true,
                    'latency_ms' => $latency,
                    'reply' => trim($reply),
                    'model' => $model,
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => $response->status(),
                'error' => $response->body(),
            ], 400);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testDiscord(Request $request): JsonResponse
    {
        $botToken = trim($request->input('bot_token') ?: $this->settings->get('settings::bytenodes::discord:bot_token', env('DISCORD_BOT_TOKEN', '')));
        $guildId = trim($request->input('guild_id') ?: $this->settings->get('settings::bytenodes::discord:guild_id', env('DISCORD_GUILD_ID', '')));

        if (empty($botToken)) {
            return response()->json(['success' => false, 'error' => 'Bot token tidak boleh kosong.'], 400);
        }

        try {
            // Check bot identity
            $botRes = Http::withHeaders([
                'Authorization' => 'Bot ' . $botToken,
            ])->timeout(8)->get('https://discord.com/api/v10/users/@me');

            if (!$botRes->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Token Bot tidak valid: ' . $botRes->body(),
                ], 400);
            }

            $botData = $botRes->json();
            $guildData = null;

            if (!empty($guildId)) {
                $guildRes = Http::withHeaders([
                    'Authorization' => 'Bot ' . $botToken,
                ])->timeout(8)->get("https://discord.com/api/v10/guilds/{$guildId}");
                if ($guildRes->successful()) {
                    $guildData = $guildRes->json();
                }
            }

            return response()->json([
                'success' => true,
                'bot_username' => $botData['username'] . (isset($botData['discriminator']) && $botData['discriminator'] !== '0' ? '#' . $botData['discriminator'] : ''),
                'bot_id' => $botData['id'],
                'guild_name' => $guildData['name'] ?? null,
                'guild_id' => $guildId,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
