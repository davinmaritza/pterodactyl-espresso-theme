<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordController extends ClientApiController
{
    private function getSettings(): \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
    {
        return app(\Pterodactyl\Contracts\Repository\SettingsRepositoryInterface::class);
    }

    private function getBotToken(): ?string
    {
        return $this->getSettings()->get('settings::bytenodes::discord:bot_token') ?: config('services.discord.bot_token', env('DISCORD_BOT_TOKEN'));
    }

    private function getGuildId(): string
    {
        return (string) ($this->getSettings()->get('settings::bytenodes::discord:guild_id') ?: config('services.discord.guild_id', env('DISCORD_GUILD_ID')) ?: '1445078072414965985');
    }

    private function getRoleId(): string
    {
        return (string) ($this->getSettings()->get('settings::bytenodes::discord:role_id') ?: config('services.discord.role_id') ?: env('DISCORD_ROLE_ID') ?: '1445078072830197841');
    }

    private function getReviewWebhookUrl(): ?string
    {
        return $this->getSettings()->get('settings::bytenodes::discord:review_webhook_url') ?: env('DISCORD_REVIEW_WEBHOOK_URL');
    }
    /**
     * Return the authenticated user's Discord profile and roles.
     */
    public function index(Request $request): array
    {
        $user = $request->user();
        $isOwner = \Pterodactyl\Models\Server::where('owner_id', $user->id)->exists();
        $hasActiveServer = $isOwner || (bool) $user->root_admin;
        $roleId = $this->getRoleId();

        if (empty($user->discord_id)) {
            return [
                'success' => true,
                'data' => [
                    'is_linked' => false,
                    'discord_id' => null,
                    'username' => null,
                    'discord_tag' => null,
                    'avatar' => null,
                    'avatar_url' => null,
                    'roles' => [],
                    'has_active_server' => $hasActiveServer,
                    'role_id' => $roleId,
                    'role_name' => 'ByteClient',
                    'can_claim' => false,
                    'is_role_claimed' => false,
                ],
            ];
        }

        $discordId = (string) $user->discord_id;
        $botToken = $this->getBotToken();
        $guildId = $this->getGuildId();

        // Check if manual refresh requested
        $forceRefresh = $request->has('refresh') || $request->has('sync');
        if ($forceRefresh) {
            Cache::forget("discord_profile_{$discordId}");
        }

        $isLocallyClaimed = Cache::has("discord_role_claimed_{$discordId}");

        // Check cached profile
        $cached = $forceRefresh ? null : Cache::get("discord_profile_{$discordId}");
        if ($cached && is_array($cached)) {
            $cachedAvatar = $cached['avatar'] ?? $cached['avatar_url'] ?? null;
            if ($cachedAvatar && !str_contains($cachedAvatar, '/embed/avatars/')) {
                $roles = $cached['roles'] ?? [];
                $isClaimed = in_array($roleId, array_column($roles, 'id')) || $isLocallyClaimed;
                return [
                    'success' => true,
                    'data' => array_merge([
                        'is_linked' => true,
                        'discord_id' => $discordId,
                        'avatar' => $cachedAvatar,
                        'avatar_url' => $cachedAvatar,
                        'has_active_server' => $hasActiveServer,
                        'role_id' => $roleId,
                        'role_name' => 'ByteClient',
                        'can_claim' => $hasActiveServer && !$isClaimed,
                        'is_role_claimed' => $isClaimed,
                    ], $cached, [
                        'is_role_claimed' => $isClaimed,
                        'can_claim' => $hasActiveServer && !$isClaimed,
                    ]),
                ];
            }
        }

        $username = $user->username;
        $discordTag = null;
        $avatarUrl = $user->discord_avatar ?: null;
        $roles = [];

        // Special static roles for owner suzirz
        if ($discordId === '377411544914591744') {
            $username = 'suzirz';
            $discordTag = 'suzirz';
            $avatarUrl = "https://cdn.discordapp.com/avatars/377411544914591744/a0d669726318733c30cc22539410b578.png?size=128";
            $roles = [
                ['id' => 'founder', 'name' => 'Founder', 'color' => '#EBF5EE'],
                ['id' => 'ceo', 'name' => 'Chief Executive Officer', 'color' => '#F1C40F'],
                ['id' => 'staff', 'name' => 'Staff', 'color' => '#1ABC9C'],
                ['id' => 'bytetycoon', 'name' => 'ByteTycoon', 'color' => '#F39C12'],
                ['id' => 'byteelite', 'name' => 'ByteElite', 'color' => '#00BCD4'],
                ['id' => $roleId, 'name' => 'ByteClient', 'color' => '#BFA89E'],
                ['id' => 'bytemember', 'name' => 'ByteMember', 'color' => '#8B786D'],
                ['id' => 'verified_client', 'name' => 'Verified Client', 'color' => '#BFA89E'],
                ['id' => 'grafana_alert', 'name' => 'Grafana Alert', 'color' => '#E74C3C'],
            ];
        }

        // Check public ByteNodes Discord invite if avatar still needed
        if (empty($avatarUrl) || str_contains($avatarUrl, '/embed/avatars/')) {
            try {
                $inviteRes = Http::timeout(3)->get('https://discord.com/api/v10/invites/FXSRz5evqr');
                if ($inviteRes->successful()) {
                    $inviter = $inviteRes->json()['inviter'] ?? null;
                    if ($inviter && ((string)($inviter['id'] ?? '')) === $discordId) {
                        $username = $inviter['global_name'] ?? $inviter['username'] ?? $username;
                        $discordTag = $inviter['username'] ?? null;
                        if (!empty($inviter['avatar'])) {
                            $avatarUrl = "https://cdn.discordapp.com/avatars/{$discordId}/{$inviter['avatar']}.png?size=128";
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        // Attempt Discord API fetch via Bot Token
        if (!empty($botToken) && !empty($guildId)) {
            try {
                $guildRoles = Cache::remember("discord_guild_roles_{$guildId}", 3600, function () use ($botToken, $guildId) {
                    $r = Http::withToken($botToken, 'Bot')->timeout(4)->get("https://discord.com/api/v10/guilds/{$guildId}/roles");
                    if ($r->successful()) {
                        $map = [];
                        foreach ($r->json() as $role) {
                            $colorHex = $role['color'] ? ('#' . str_pad(dechex($role['color']), 6, '0', STR_PAD_LEFT)) : '#BFA89E';
                            $map[$role['id']] = [
                                'name' => $role['name'],
                                'color' => $colorHex,
                                'position' => $role['position'] ?? 0,
                            ];
                        }
                        return $map;
                    }
                    return [];
                });

                $memberRes = Http::withToken($botToken, 'Bot')->timeout(4)->get("https://discord.com/api/v10/guilds/{$guildId}/members/{$discordId}");
                if ($memberRes->successful()) {
                    $m = $memberRes->json();
                    $u = $m['user'] ?? [];
                    $username = $m['nick'] ?? $u['global_name'] ?? $u['username'] ?? $username;
                    $discordTag = $u['username'] ?? null;
                    if (!empty($u['avatar'])) {
                        $avatarUrl = "https://cdn.discordapp.com/avatars/{$discordId}/{$u['avatar']}.png?size=128";
                    }

                    $memberRoleIds = $m['roles'] ?? [];
                    $mapped = [];
                    foreach ($memberRoleIds as $rid) {
                        if (isset($guildRoles[$rid]) && !in_array(trim($guildRoles[$rid]['name']), ['@everyone', '·', '.', ''])) {
                            $mapped[] = [
                                'id' => $rid,
                                'name' => $guildRoles[$rid]['name'],
                                'color' => $guildRoles[$rid]['color'],
                                'position' => $guildRoles[$rid]['position'] ?? 0,
                            ];
                        }
                    }
                    usort($mapped, fn($a, $b) => ($b['position'] ?? 0) <=> ($a['position'] ?? 0));
                    if (!empty($mapped)) {
                        $roles = $mapped;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("DiscordController profile fetch warning: " . $e->getMessage());
            }
        }

        // Role fallback for active users and server owners
        if (empty($roles)) {
            if ($user->root_admin) {
                $roles = [
                    ['id' => 'staff', 'name' => 'Staff', 'color' => '#BFA89E'],
                    ['id' => 'bytetycoon', 'name' => 'ByteTycoon', 'color' => '#dd9754'],
                    ['id' => 'byteelite', 'name' => 'ByteElite', 'color' => '#BFA89E'],
                    ['id' => $roleId, 'name' => 'ByteClient', 'color' => '#52b788'],
                    ['id' => 'verified_client', 'name' => 'Verified Client', 'color' => '#BFA89E'],
                    ['id' => 'bytemember', 'name' => 'ByteMember', 'color' => '#8B786D'],
                ];
            } elseif ($hasActiveServer || $isLocallyClaimed) {
                $roles = [
                    ['id' => $roleId, 'name' => 'ByteClient', 'color' => '#52b788'],
                    ['id' => 'verified_client', 'name' => 'Verified Client', 'color' => '#BFA89E'],
                    ['id' => 'bytemember', 'name' => 'ByteMember', 'color' => '#8B786D'],
                ];
            } else {
                $roles = [
                    ['id' => 'bytemember', 'name' => 'ByteMember', 'color' => '#8B786D'],
                ];
            }
        }

        if (empty($avatarUrl)) {
            $avatarUrl = "https://cdn.discordapp.com/embed/avatars/" . (abs(intval(substr($discordId, -4))) % 5) . ".png";
        } else {
            try {
                if ($user->discord_avatar !== $avatarUrl) {
                    $user->discord_avatar = $avatarUrl;
                    $user->save();
                }
            } catch (\Throwable $e) {
                // Ignore DB save error
            }
        }

        $isRoleClaimed = in_array($roleId, array_column($roles, 'id')) || $isLocallyClaimed;

        $profileData = [
            'is_linked' => true,
            'discord_id' => $discordId,
            'username' => $username,
            'discord_tag' => $discordTag,
            'avatar' => $avatarUrl,
            'avatar_url' => $avatarUrl,
            'roles' => $roles,
            'has_active_server' => $hasActiveServer,
            'role_id' => $roleId,
            'role_name' => 'ByteClient',
            'can_claim' => $hasActiveServer && !$isRoleClaimed,
            'is_role_claimed' => $isRoleClaimed,
        ];

        Cache::put("discord_profile_{$discordId}", $profileData, 86400 * 7);

        return [
            'success' => true,
            'data' => $profileData,
        ];
    }

    /**
     * Claim / sync the ByteClient role in Discord if the user has an active server.
     */
    public function syncRole(Request $request): array
    {
        $user = $request->user();

        if (empty($user->discord_id)) {
            return [
                'success' => false,
                'message' => 'Akun Discord belum terhubung. Silakan hubungkan akun Discord terlebih dahulu di menu Akun.',
            ];
        }

        $discordId = (string) $user->discord_id;
        $botToken = $this->getBotToken();
        $guildId = $this->getGuildId();
        $roleId = $this->getRoleId();

        $hasActiveServer = \Pterodactyl\Models\Server::where('owner_id', $user->id)->exists() || (bool) $user->root_admin;

        if (!$hasActiveServer) {
            Cache::forget("discord_role_claimed_{$discordId}");
            Cache::forget("discord_profile_{$discordId}");

            return [
                'success' => false,
                'message' => 'Anda belum memiliki server aktif. Role ByteClient hanya dapat diklaim oleh pengguna dengan server aktif.',
                'has_active_server' => false,
            ];
        }

        // User is eligible! Assign ByteClient role via Discord REST API or notify staff
        $roleAssigned = false;
        $errorMessage = null;
        $isBotOfflineOrUnauthorized = false;

        if (!empty($botToken) && !empty($guildId) && !empty($roleId)) {
            try {
                $res = Http::withToken($botToken, 'Bot')
                    ->timeout(6)
                    ->put("https://discord.com/api/v10/guilds/{$guildId}/members/{$discordId}/roles/{$roleId}");

                if ($res->successful() || $res->status() === 204) {
                    $roleAssigned = true;
                } else {
                    $body = $res->json() ?? [];
                    $statusCode = $res->status();
                    Log::warning("Discord role assign response: {$statusCode} - " . json_encode($body));

                    if ($statusCode === 401 || $statusCode === 403) {
                        $isBotOfflineOrUnauthorized = true;
                    } elseif ($statusCode === 404 || ($body['code'] ?? 0) === 10007) {
                        $errorMessage = 'Akun Discord Anda belum bergabung di server Discord ByteNodes. Silakan gabung terlebih dahulu di https://dsc.gg/bytenodes lalu klik klaim kembali.';
                    } else {
                        $isBotOfflineOrUnauthorized = true;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Discord role assign exception: " . $e->getMessage());
                $isBotOfflineOrUnauthorized = true;
            }
        } else {
            $isBotOfflineOrUnauthorized = true;
        }

        // If Discord Bot API failed / 401 Unauthorized, dispatch notification to Staff Review Webhook
        if (!$roleAssigned && $isBotOfflineOrUnauthorized) {
            $webhookUrl = $this->getReviewWebhookUrl();
            if (!empty($webhookUrl)) {
                try {
                    $serverCount = \Pterodactyl\Models\Server::where('owner_id', $user->id)->count();
                    Http::timeout(5)->post($webhookUrl, [
                        'content' => "👑 **Klaim Role ByteClient Terverifikasi**\nPengguna **{$user->username}** (<@{$discordId}>) memiliki {$serverCount} server aktif di panel.\n*(Sistem telah memverifikasi kelayakan pengguna. Silakan sinkronkan role di Discord).* ",
                        'embeds' => [
                            [
                                'title' => 'Verifikasi Klaim Role ByteClient',
                                'color' => 0xBFA89E,
                                'fields' => [
                                    ['name' => 'Username Panel', 'value' => (string) $user->username, 'inline' => true],
                                    ['name' => 'Discord Tag / ID', 'value' => "<@{$discordId}> (`{$discordId}`)", 'inline' => true],
                                    ['name' => 'Jumlah Server Aktif', 'value' => (string) $serverCount, 'inline' => true],
                                ],
                                'footer' => [
                                    'text' => 'Bytenodes Automated Role Dispatcher'
                                ],
                                'timestamp' => now()->toIso8601String(),
                            ]
                        ]
                    ]);
                } catch (\Throwable $wErr) {
                    Log::warning("DiscordController webhook dispatch error: " . $wErr->getMessage());
                }
            }

            Cache::put("discord_role_claimed_{$discordId}", true, 86400 * 30);
            Cache::forget("discord_profile_{$discordId}");

            return [
                'success' => true,
                'message' => 'Permintaan klaim role ByteClient berhasil dicatat dan diverifikasi! Staf ByteNodes telah dinotifikasi untuk aktivasi role Discord Anda.',
                'role_id' => $roleId,
                'role_name' => 'ByteClient',
                'is_role_claimed' => true,
            ];
        }

        Cache::forget("discord_profile_{$discordId}");

        if ($roleAssigned) {
            Cache::put("discord_role_claimed_{$discordId}", true, 86400 * 30);
            return [
                'success' => true,
                'message' => 'Selamat! Role ByteClient berhasil diklaim dan aktif di server Discord ByteNodes.',
                'role_id' => $roleId,
                'role_name' => 'ByteClient',
                'is_role_claimed' => true,
            ];
        }

        return [
            'success' => false,
            'message' => $errorMessage ?: 'Gagal memberikan role Discord. Pastikan Anda telah bergabung di server Discord ByteNodes (dsc.gg/bytenodes).',
        ];
    }

    /**
     * Unlink the user's Discord account.
     */
    public function unlink(Request $request): array
    {
        $user = $request->user();
        if ($user->username === 'demo' || $user->email === 'demo@bytenodes.id') {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Demo account is read-only. Discord unlinking is disabled.');
        }
        if ($user->discord_id) {
            Cache::forget("discord_role_claimed_{$user->discord_id}");
            Cache::forget("discord_profile_{$user->discord_id}");
            $user->discord_id = null;
            if (isset($user->discord_avatar)) {
                $user->discord_avatar = null;
            }
            $user->save();
        }

        return [
            'success' => true,
            'message' => 'Akun Discord berhasil dilepas.',
        ];
    }
}
