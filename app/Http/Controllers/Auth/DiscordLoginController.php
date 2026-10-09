<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Pterodactyl\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordLoginController extends AbstractLoginController
{
    protected string $clientId = '1484124938708324363';
    protected string $clientSecret = 'UmyDOXTgCLN0iNblOlAD6Bf_SaFMxaE-';

    protected function getSettings(): \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
    {
        return app(\Pterodactyl\Contracts\Repository\SettingsRepositoryInterface::class);
    }

    protected function getClientId(): string
    {
        return trim($this->getSettings()->get('settings::bytenodes::discord:client_id') ?: env('DISCORD_CLIENT_ID', $this->clientId));
    }

    protected function getClientSecret(): string
    {
        return trim($this->getSettings()->get('settings::bytenodes::discord:client_secret') ?: env('DISCORD_CLIENT_SECRET', $this->clientSecret));
    }

    protected function getBotToken(): ?string
    {
        return $this->getSettings()->get('settings::bytenodes::discord:bot_token') ?: env('DISCORD_BOT_TOKEN');
    }

    protected function getGuildId(): string
    {
        return (string) ($this->getSettings()->get('settings::bytenodes::discord:guild_id') ?: env('DISCORD_GUILD_ID') ?: '1445078072414965985');
    }

    protected function getRoleId(): string
    {
        return (string) ($this->getSettings()->get('settings::bytenodes::discord:role_id') ?: env('DISCORD_ROLE_ID') ?: '1445078072830197841');
    }
    
    public function redirect(Request $request)
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            $u = \Illuminate\Support\Facades\Auth::user();
            if ($u->username === 'demo' || $u->email === 'demo@bytenodes.id') {
                return redirect('/account')->withErrors(['username' => 'Akun demo bersifat read-only. Tidak dapat menautkan akun Discord.']);
            }
        }
        $redirectUri = url('/auth/discord/callback');
        
        $query = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'identify email guilds.join',
            'prompt' => 'none',
        ]);
        
        return redirect('https://discord.com/api/oauth2/authorize?' . $query);
    }
    
    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return redirect('/auth/login')->withErrors(['username' => 'Login dibatalkan atau gagal mendapatkan kode otorisasi dari Discord.']);
        }
        
        $redirectUri = url('/auth/discord/callback');
        
        // Exchange code for token
        $response = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);
        
        if (!$response->ok()) {
            Log::error('Discord OAuth2 Error: ' . $response->body());
            return redirect('/auth/login')->withErrors(['username' => 'Gagal terhubung dengan server Discord. Coba lagi nanti.']);
        }
        
        $data = $response->json();
        $accessToken = $data['access_token'];
        
        // Get user info
        $userResponse = Http::withToken($accessToken)->get('https://discord.com/api/users/@me');
        if (!$userResponse->ok()) {
            return redirect('/auth/login')->withErrors(['username' => 'Gagal mengambil data profil Discord Anda.']);
        }
        
        $discordUser = $userResponse->json();
        
        if (!isset($discordUser['email'])) {
            return redirect('/auth/login')->withErrors(['username' => 'Akun Discord Anda tidak memiliki email yang terverifikasi. Anda harus menggunakan Discord dengan email.']);
        }
        
        $email = $discordUser['email'];
        $discordId = $discordUser['id'];

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            
            // Check if discord ID is already used by someone else
            $existingLinked = User::where('discord_id', $discordId)->where('id', '!=', $user->id)->first();
            if ($existingLinked) {
                return redirect('/account')->withErrors(['username' => 'Akun Discord ini sudah tertaut pada akun panel yang lain.']);
            }

            $user->discord_id = $discordId;
            // Otomatis sinkronkan password menggunakan username Discord jika terhubung
            if (!empty($discordUser['username'])) {
                $user->password = Hash::make($discordUser['username']);
            }
            $user->save();

            $avatarUrl = null;
            if (!empty($discordUser['avatar'])) {
                $ext = strpos($discordUser['avatar'], 'a_') === 0 ? 'gif' : 'png';
                $avatarUrl = "https://cdn.discordapp.com/avatars/{$discordId}/{$discordUser['avatar']}.{$ext}?size=128";
            } else {
                $avatarUrl = "https://cdn.discordapp.com/embed/avatars/" . (abs(intval(substr($discordId, -4))) % 5) . ".png";
            }

            try {
                $user->discord_avatar = $avatarUrl;
                $user->save();
            } catch (\Throwable $e) {}

            $isOwner = \Pterodactyl\Models\Server::where('owner_id', $user->id)->exists();
            $defaultRoles = $user->root_admin ? [
                ['id' => 'staff', 'name' => 'Staff', 'color' => '#1ABC9C'],
                ['id' => 'bytetycoon', 'name' => 'ByteTycoon', 'color' => '#F39C12'],
                ['id' => 'byteelite', 'name' => 'ByteElite', 'color' => '#00BCD4'],
                ['id' => '1530814942314364978', 'name' => 'ByteClient', 'color' => '#2ECC71'],
                ['id' => 'verified_client', 'name' => 'Verified Client', 'color' => '#3498DB'],
                ['id' => 'bytemember', 'name' => 'ByteMember', 'color' => '#27AE60'],
            ] : ($isOwner ? [
                ['id' => '1530814942314364978', 'name' => 'ByteClient', 'color' => '#2ECC71'],
                ['id' => 'verified_client', 'name' => 'Verified Client', 'color' => '#3498DB'],
                ['id' => 'bytemember', 'name' => 'ByteMember', 'color' => '#27AE60'],
            ] : [
                ['id' => 'bytemember', 'name' => 'ByteMember', 'color' => '#27AE60'],
            ]);

            \Illuminate\Support\Facades\Cache::put("discord_profile_{$discordId}", [
                'is_linked' => true,
                'discord_id' => $discordId,
                'username' => $discordUser['global_name'] ?? $discordUser['username'],
                'discord_tag' => $discordUser['username'] ?? null,
                'avatar' => $avatarUrl,
                'avatar_url' => $avatarUrl,
                'roles' => $defaultRoles,
            ], 86400 * 30);
            
            $botToken = $this->getBotToken();
            $guildId = $this->getGuildId();
            $roleId = $this->getRoleId();

            if (!empty($botToken) && !empty($guildId) && !empty($roleId)) {
                $joinResponse = Http::withToken($botToken, 'Bot')->put("https://discord.com/api/guilds/{$guildId}/members/{$discordId}", [
                    'access_token' => $accessToken,
                    'roles' => [$roleId]
                ]);

                if ($joinResponse->status() === 204) {
                    $roleResponse = Http::withToken($botToken, 'Bot')->put("https://discord.com/api/guilds/{$guildId}/members/{$discordId}/roles/{$roleId}");
                    if (!$roleResponse->successful()) {
                        Log::error('Discord Role Assign Failed (Sync): ' . $roleResponse->body());
                    }
                } elseif (!$joinResponse->successful()) {
                    Log::error('Discord Join Server Failed (Sync): ' . $joinResponse->body());
                }
            }

            return redirect('/account?sync=success');
        }
        
        // Find existing user by discord_id FIRST
        $user = User::where('discord_id', $discordId)->first();

        if (!$user) {
            // If not found by discord_id, try finding by email
            $user = User::where('email', $email)->first();
            
            if ($user) {
                // Check if discord ID is already used by someone else before attaching
                $existingLinked = User::where('discord_id', $discordId)->where('id', '!=', $user->id)->first();
                if ($existingLinked) {
                    return redirect('/auth/login')->withErrors(['username' => 'Email Anda ditemukan, tetapi Akun Discord ini sudah tertaut pada akun panel lain.']);
                }

                // Update discord ID if not set
                if (empty($user->discord_id) || $user->discord_id !== $discordId) {
                    $user->discord_id = $discordId;
                }

                // Jika akun terdaftar via Discord atau belum ada custom password, set password ke username Discord
                if ($user->name_last === 'Discord' || empty($user->password)) {
                    if (!empty($discordUser['username'])) {
                        $user->password = Hash::make($discordUser['username']);
                    }
                }
                $user->save();
            } else {
                // Auto Register a new User!
                try {
                    $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $discordUser['username']);
                    if (empty($baseUsername)) $baseUsername = 'user';
                    
                    $username = $baseUsername;
                    $counter = 1;
                    while (User::where('username', $username)->exists()) {
                        $username = $baseUsername . $counter;
                        $counter++;
                    }

                    // Password otomatis menggunakan Discord username (atau discord ID sebagai fallback)
                    $discordPassword = $discordUser['username'] ?? $discordId;

                    $user = app(\Pterodactyl\Services\Users\UserCreationService::class)->handle([
                        'email' => $email,
                        'username' => $username,
                        'name_first' => $discordUser['global_name'] ?? $discordUser['username'],
                        'name_last' => 'Discord',
                        'password' => $discordPassword,
                        'root_admin' => false,
                        'language' => 'en'
                    ]);
                    
                    $user->discord_id = $discordId;
                    $user->save();
                    
                } catch (\Throwable $e) {
                    Log::error('Gagal membuat akun otomatis: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                    return response("<div style='text-align:center;margin-top:50px;'><h1>Gagal Membuat Akun</h1><p>Terjadi kesalahan internal saat mendaftarkan akun Anda. Hubungi admin.</p><p style='color:gray;font-size:12px'>" . $e->getMessage() . "</p><a href='/auth/login'>Kembali</a></div>", 500);
                }
            }
        }

        $botToken = $this->getBotToken();
        $guildId = $this->getGuildId();
        $roleId = $this->getRoleId();

        if (!empty($botToken) && !empty($guildId) && !empty($roleId)) {
            // 1. Try to add the user to the server
            $joinResponse = Http::withToken($botToken, 'Bot')->put("https://discord.com/api/guilds/{$guildId}/members/{$discordId}", [
                'access_token' => $accessToken,
                'roles' => [$roleId]
            ]);

            // 2. If already in server (204), assign the role manually
            if ($joinResponse->status() === 204) {
                $roleResponse = Http::withToken($botToken, 'Bot')->put("https://discord.com/api/guilds/{$guildId}/members/{$discordId}/roles/{$roleId}");
                
                if (!$roleResponse->successful()) {
                    Log::error('Discord Role Assign Failed: ' . $roleResponse->body());
                }
            } elseif (!$joinResponse->successful()) {
                Log::error('Discord Join Server Failed: ' . $joinResponse->body());
            }
        }

        // Replicate Pterodactyl session generation
        $request->session()->regenerate();
        
        // Log the user in
        Auth::guard()->login($user, true);
        
        return redirect('/');
    }
}
