<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client as GuzzleClient;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class ServerAiController extends ClientApiController
{
    private const MAX_ATTEMPTS = 10;
    private const WINDOW_SECONDS = 259200; // 3 hari (72 jam)

    private function getSettings(): \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
    {
        return app(\Pterodactyl\Contracts\Repository\SettingsRepositoryInterface::class);
    }

    private function isAiEnabled(): bool
    {
        return (bool) $this->getSettings()->get('settings::bytenodes::ai:enabled', true);
    }

    private function getApiKey(): string
    {
        return trim($this->getSettings()->get('settings::bytenodes::ai:api_key') ?: env('NINEROUTER_API_KEY', 'sk-1e6c6b93a4e6c14d-podsu7-f1e560e2'));
    }

    private function getBaseUrl(): string
    {
        return rtrim($this->getSettings()->get('settings::bytenodes::ai:base_url') ?: env('NINEROUTER_BASE_URL', 'https://routernines.bytenodes.icu/v1'), '/');
    }

    private function getModel(): string
    {
        $m = trim($this->getSettings()->get('settings::bytenodes::ai:model') ?: env('NINEROUTER_MODEL', 'ag/gemini-3.8-flash'));
        return ($m === 'bytenodes' || empty($m)) ? 'ag/gemini-3.8-flash' : $m;
    }

    private function getMaxAttempts(): int
    {
        return max(1, (int) ($this->getSettings()->get('settings::bytenodes::ai:max_attempts') ?: self::MAX_ATTEMPTS));
    }

    private function getWindowSeconds(): int
    {
        $hours = (int) ($this->getSettings()->get('settings::bytenodes::ai:window_hours') ?: 72);
        return max(3600, $hours * 3600);
    }

    /**
     * Get current AI quota status for this server.
     */
    public function quota(Request $request, Server $server): JsonResponse
    {
        $maxAttempts = $this->getMaxAttempts();
        $windowSeconds = $this->getWindowSeconds();

        $cacheKey = "ai_quota_server_{$server->id}";
        $quotaData = Cache::get($cacheKey, [
            'used' => 0,
            'reset_at' => time() + $windowSeconds,
        ]);

        $remaining = max(0, $maxAttempts - $quotaData['used']);
        $hoursLeft = max(1, ceil(($quotaData['reset_at'] - time()) / 3600));

        return new JsonResponse([
            'success' => true,
            'used' => $quotaData['used'],
            'remaining' => $remaining,
            'max' => $maxAttempts,
            'hours_until_reset' => $hoursLeft,
            'is_enabled' => $this->isAiEnabled(),
        ]);
    }

    /**
     * Alias for quota route compatibility.
     */
    public function getQuota(Request $request, Server $server): JsonResponse
    {
        return $this->quota($request, $server);
    }

    /**
     * Handle ChatGPT-style interactive conversation for a server.
     */
    public function chat(Request $request, Server $server): JsonResponse
    {
        $this->validate($request, [
            'message' => 'required|string|max:1500',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
            'recent_logs' => 'nullable|array|max:40',
            'recent_logs.*' => 'string',
        ]);

        if (!$this->isAiEnabled()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Layanan AI Assistant sedang dinonaktifkan oleh administrator.',
            ], 403);
        }

        $maxAttempts = $this->getMaxAttempts();
        $windowSeconds = $this->getWindowSeconds();

        $cacheKey = "ai_quota_server_{$server->id}";
        $quotaData = Cache::get($cacheKey, [
            'used' => 0,
            'reset_at' => time() + $windowSeconds,
        ]);

        // Cek jika kuota habis
        if ($quotaData['used'] >= $maxAttempts) {
            $hoursLeft = max(1, ceil(($quotaData['reset_at'] - time()) / 3600));
            return new JsonResponse([
                'success' => false,
                'error' => "Batas konsultasi AI untuk server ini telah habis ({$maxAttempts} sesi). Kuota akan otomatis reset dalam {$hoursLeft} jam.",
                'remaining' => 0,
                'hours_until_reset' => $hoursLeft,
            ], 429);
        }

        $userMessage = trim($request->input('message'));
        $history = $request->input('history', []);
        $recentLogs = $request->input('recent_logs', []);
        $logsText = !empty($recentLogs) ? implode("\n", array_slice($recentLogs, -20)) : "Tidak ada log error/crash yang ditangkap.";

        $apiKey = $this->getApiKey();
        $baseUrl = $this->getBaseUrl();
        $model = $this->getModel();

        $systemPrompt = "You are the official ByteNodes AI Server Engineer, specialized in game servers (Minecraft Java/Paper/Purpur/Spigot/Velocity/Fabric/Forge and Bedrock/BDS/Geyser/PocketMine).
Tone: Friendly, concise, sharp, highly practical (sound like an elite devops/sysadmin, never robotic or verbose).
Language Rule: MATCH THE USER'S LANGUAGE NATURALLY. If the user speaks Indonesian (Bahasa Indonesia / gaul / santai), respond fluently in Indonesian. If the user speaks English, respond in English. Always mirror the user's language choice smoothly.

Current Server Context:
- Server Name: {$server->name}
- UUID: {$server->uuidShort}
- Node: {$server->node->name}
- IP:Port Allocation: {$server->allocation->ip}:{$server->allocation->port}
- Recent Console Logs:
```
{$logsText}
```

Instructions:
1. Diagnose the issue directly using the user query and recent server logs provided above.
2. Give clear, actionable instructions (specify exact configuration filenames like server.properties, spigot.yml, paper.yml and where to find them in File Manager).
3. Keep solutions structured with bold headings or numbered steps. No fluff.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Masukkan history chat sebelumnya (maksimal 6 interaksi terakhir)
        foreach (array_slice($history, -6) as $h) {
            $messages[] = [
                'role' => $h['role'],
                'content' => $h['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        $client = new GuzzleClient([
            'base_uri' => $baseUrl,
            'timeout' => 35,
            'http_errors' => false,
        ]);

        $modelsToTry = array_values(array_unique([$model, 'ag/gemini-3.8-flash', 'ag/gemini-3.7-flash-medium']));
        $replyContent = null;
        $usedModel = null;

        foreach ($modelsToTry as $currentModel) {
            try {
                $response = $client->post('/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $currentModel,
                        'stream' => false,
                        'messages' => $messages,
                        'temperature' => 0.4,
                        'max_tokens' => 1200,
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $body = json_decode((string) $response->getBody(), true);

                if ($statusCode === 200 && isset($body['choices'][0]['message']['content'])) {
                    $content = trim($body['choices'][0]['message']['content']);
                    if (!str_contains($content, 'is no longer available') && !str_contains($content, 'Please switch to')) {
                        $replyContent = $content;
                        $usedModel = $currentModel;
                        break;
                    }
                }
            } catch (Exception $e) {
                // Next fallback model
            }
        }

        if (!$replyContent) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Koneksi ke gateway AI mengalami kendala sementara. Silakan coba kirim ulang pesan Anda.',
            ], 500);
        }

        // Hitung penggunaan kuota HANYA jika pesan sukses dijawab
        $quotaData['used'] += 1;
        $ttl = max(60, $quotaData['reset_at'] - time());
        Cache::put($cacheKey, $quotaData, $ttl);

        $remaining = max(0, self::MAX_ATTEMPTS - $quotaData['used']);
        $hoursLeft = max(1, ceil(($quotaData['reset_at'] - time()) / 3600));

        return new JsonResponse([
            'success' => true,
            'reply' => $replyContent,
            'remaining' => $remaining,
            'hours_until_reset' => $hoursLeft,
            'model' => $usedModel,
        ]);
    }

    /**
     * Explain crash/error logs directly.
     */
    public function explain(Request $request, Server $server): JsonResponse
    {
        $this->validate($request, [
            'logs' => 'required|array|min:1',
            'logs.*' => 'string',
        ]);

        $logLines = array_slice($request->input('logs'), -30);
        $logsText = implode("\n", $logLines);

        if (!$this->isAiEnabled()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Layanan AI Diagnostic sedang dinonaktifkan oleh administrator.',
            ], 403);
        }

        $apiKey = $this->getApiKey();
        $baseUrl = $this->getBaseUrl();
        $model = $this->getModel();

        $systemPrompt = "Kamu adalah Bytenodes Server Diagnostic AI, teknisi ahli server game Minecraft (Java/Paper/Purpur/Spigot/Fabric/Forge) dan Bedrock (BDS/Geyser/PocketMine).
Tugasmu adalah menganalisis baris error crash server berikut dan memberikan ringkasan diagnosis dalam bahasa yang ramah, ringkas, dan praktis bagi pemilik server.

Format output WAJIB rapi seperti ini:
🔍 **Penyebab:**
(Jelaskan secara singkat apa yang menyebabkan error/crash tersebut dalam 1-2 kalimat padat)

🛠️ **Langkah Solusi:**
1. (Langkah aksi perbaikan nyata yang harus dilakukan pemilik server di File Manager / Panel)
2. (Langkah lanjutan atau restart)

Hindari basa-basi panjang, langsung berikan solusi tepat sasaran!";

        $client = new GuzzleClient([
            'base_uri' => $baseUrl,
            'timeout' => 25,
            'http_errors' => false,
        ]);

        $modelsToTry = array_values(array_unique([$model, 'ag/gemini-3.8-flash', 'ag/gemini-3.7-flash-medium']));
        $lastResponse = null;

        foreach ($modelsToTry as $currentModel) {
            try {
                $response = $client->post('/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $currentModel,
                        'stream' => false,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => "Server Name: {$server->name}\nBerikut log error yang dialami server:\n\n{$logsText}"],
                        ],
                        'temperature' => 0.3,
                        'max_tokens' => 800,
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $body = json_decode((string) $response->getBody(), true);

                if ($statusCode === 200 && isset($body['choices'][0]['message']['content'])) {
                    $content = trim($body['choices'][0]['message']['content']);
                    if (!str_contains($content, 'is no longer available') && !str_contains($content, 'Please switch to')) {
                        return new JsonResponse([
                            'success' => true,
                            'model' => $currentModel,
                            'explanation' => $content,
                        ]);
                    }
                    $lastResponse = $content;
                }
            } catch (Exception $e) {
                // Fallback
            }
        }

        if ($lastResponse) {
            return new JsonResponse([
                'success' => true,
                'model' => 'fallback',
                'explanation' => $lastResponse,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'error' => 'Gagal menghubungi AI diagnostic gateway. Silakan coba sesaat lagi.',
        ], 500);
    }
}
