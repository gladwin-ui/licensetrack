<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class FonnteDeviceChecker
{
    /**
     * Cache TTL for device status (10 minutes).
     */
    const CACHE_TTL = 600;

    /**
     * Cache key for device info.
     */
    const CACHE_KEY = 'fonnte_device_status';

    /**
     * Get the active Fonnte token (from settings DB with fallback to .env config).
     */
    public static function getActiveToken(): ?string
    {
        // Try to get encrypted token from database
        $encryptedToken = Setting::find('fonnte_token')?->value;

        if (filled($encryptedToken)) {
            try {
                return Crypt::decryptString($encryptedToken);
            } catch (\Exception $e) {
                Log::channel('whatsapp')->warning('FonnteDeviceChecker: failed to decrypt stored token, falling back to config.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to .env / config
        return config('whatsapp.fonnte_token');
    }

    /**
     * Check Fonnte device status.
     *
     * @param  string|null  $token  If null, uses the active token from settings/env.
     * @return array{valid: bool, device: ?string, connected: bool, name: ?string, package: ?string, quota: ?int, used: ?int, expired_at: ?string, error: ?string}
     */
    public function check(?string $token = null): array
    {
        $resolvedToken = $token ?? static::getActiveToken();

        if (empty($resolvedToken)) {
            return [
                'valid'       => false,
                'device'      => null,
                'connected'   => false,
                'name'        => null,
                'package'     => null,
                'quota'       => null,
                'used'        => null,
                'expired_at'  => null,
                'error'       => 'Token Fonnte belum dikonfigurasi.',
            ];
        }

        // Log last 6 chars only — never log the full token
        $tokenSuffix = substr($resolvedToken, -6);
        Log::channel('whatsapp')->info('FonnteDeviceChecker: checking device', [
            'token_suffix' => "...{$tokenSuffix}",
        ]);

        try {
            $response = Http::withHeaders(['Authorization' => $resolvedToken])
                ->timeout(10)
                ->connectTimeout(5)
                ->post('https://api.fonnte.com/device');

            $data = $response->json() ?? [];

            Log::channel('whatsapp')->info('FonnteDeviceChecker: response received', [
                'http_status'   => $response->status(),
                'api_status'    => $data['status'] ?? null,
                'device_status' => $data['device_status'] ?? null,
            ]);

            if (empty($data['status']) || $data['status'] !== true) {
                return [
                    'valid'       => false,
                    'device'      => null,
                    'connected'   => false,
                    'name'        => null,
                    'package'     => null,
                    'quota'       => null,
                    'used'        => null,
                    'expired_at'  => null,
                    'error'       => $data['reason'] ?? 'Token tidak valid.',
                ];
            }

            return [
                'valid'       => true,
                'device'      => $data['device'] ?? null,
                'connected'   => ($data['device_status'] ?? '') === 'connect', // exact match per spec
                'name'        => $data['name'] ?? null,
                'package'     => $data['package'] ?? null,
                'quota'       => isset($data['quota']) ? (int) $data['quota'] : null, // quota comes as string
                'used'        => isset($data['messages']) ? (int) $data['messages'] : null,
                'expired_at'  => $data['expired'] ?? null,
                'error'       => null,
            ];

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('FonnteDeviceChecker: connection exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'valid'       => false,
                'device'      => null,
                'connected'   => false,
                'name'        => null,
                'package'     => null,
                'quota'       => null,
                'used'        => null,
                'expired_at'  => null,
                'error'       => 'Tidak dapat terhubung ke server Fonnte: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check device and cache the result for 10 minutes.
     * Also persists key device fields to `settings` table as a cache.
     *
     * @param  string|null  $token
     * @return array
     */
    public function checkAndCache(?string $token = null): array
    {
        $result = $this->check($token);

        // Cache in Laravel cache (10 min)
        Cache::put(self::CACHE_KEY, $result, self::CACHE_TTL);

        // Persist device info to settings table so it survives cache flush
        if ($result['valid']) {
            $now = now()->toIso8601String();
            Setting::updateOrCreate(['key' => 'fonnte_device_number'],  ['value' => $result['device'] ?? '']);
            Setting::updateOrCreate(['key' => 'fonnte_device_name'],    ['value' => $result['name'] ?? '']);
            Setting::updateOrCreate(['key' => 'fonnte_device_package'], ['value' => $result['package'] ?? '']);
            Setting::updateOrCreate(['key' => 'fonnte_device_quota'],   ['value' => (string) ($result['quota'] ?? '')]);
            Setting::updateOrCreate(['key' => 'fonnte_device_used'],    ['value' => (string) ($result['used'] ?? '')]);
            Setting::updateOrCreate(['key' => 'fonnte_device_expired'], ['value' => $result['expired_at'] ?? '']);
            Setting::updateOrCreate(['key' => 'fonnte_device_connected'], ['value' => $result['connected'] ? '1' : '0']);
            Setting::updateOrCreate(['key' => 'fonnte_device_checked_at'], ['value' => $now]);
        }

        return $result;
    }

    /**
     * Get cached device status (or null if cache is empty).
     * Does NOT make an API call.
     *
     * @return array|null
     */
    public static function getCached(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * Get the gateway dot color class based on cached/persisted device status.
     * Returns 'bg-emerald-500' (connected), 'bg-red-500' (disconnected), or 'bg-slate-400' (log/unknown).
     */
    public static function getGatewayDot(string $gateway): string
    {
        if ($gateway === 'log') {
            return 'bg-slate-400';
        }

        if ($gateway !== 'fonnte') {
            // For meta, fall back to token-filled check
            $token = config('whatsapp.access_token');
            return filled($token) ? 'bg-emerald-500' : 'bg-red-500';
        }

        // For Fonnte: read from cache first
        $cached = static::getCached();

        if ($cached !== null) {
            return ($cached['valid'] && $cached['connected']) ? 'bg-emerald-500' : 'bg-red-500';
        }

        // Fallback to persisted DB value (doesn't make API call)
        $connected = Setting::find('fonnte_device_connected')?->value;

        if ($connected !== null) {
            return $connected === '1' ? 'bg-emerald-500' : 'bg-red-500';
        }

        // No data yet — if token exists treat as unknown (red), no token as red
        $hasToken = filled(static::getActiveToken());
        return $hasToken ? 'bg-yellow-500' : 'bg-red-500';
    }
}
