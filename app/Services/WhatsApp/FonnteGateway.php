<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\FonnteDeviceChecker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FonnteGateway implements WhatsAppGateway
{
    /**
     * Send a free-text message via Fonnte API.
     *
     * @param string $phone
     * @param string $templateName
     * @param array $params
     * @param string $body
     * @return array{success: bool, wamid: ?string, error: ?string, permanent: bool}
     */
    public function send(string $phone, string $templateName, array $params, string $body = ''): array
    {
        // Token: read from settings (DB) first, fallback to .env config
        $token = FonnteDeviceChecker::getActiveToken();
        $url = 'https://api.fonnte.com/send';

        $payload = [
            'target'      => $phone,
            'message'     => $body,
            'countryCode' => '62',
        ];

        // Log request without sensitive token
        Log::channel('whatsapp')->info('FonnteGateway Request', [
            'url'     => $url,
            'target'  => $phone,
            'message' => Str::limit($body, 50),
        ]);

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->timeout(15)
                ->connectTimeout(5)
                ->post($url, $payload);

            Log::channel('whatsapp')->info('FonnteGateway Response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            $data = $response->json() ?? [];

            if ($response->successful() && !empty($data['status']) && $data['status'] === true) {
                $rawId = $data['id'] ?? null;
                $messageId = is_array($rawId)
                    ? (string) ($rawId[0] ?? '')
                    : (string) ($rawId ?? '');

                if ($messageId === '') {
                    $messageId = 'fonnte-' . Str::uuid();
                }

                return [
                    'success'   => true,
                    'wamid'     => $messageId,
                    'error'     => null,
                    'permanent' => false,
                ];
            }

            return $this->handleError($data, $response->status());

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('FonnteGateway Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success'   => false,
                'wamid'     => null,
                'error'     => 'Connection error: ' . $e->getMessage(),
                'permanent' => false,
            ];
        }
    }

    /**
     * Parse Fonnte error to determine if it is permanent.
     *
     * @param array $data
     * @param int $httpStatus
     * @return array{success: bool, wamid: ?string, error: ?string, permanent: bool}
     */
    private function handleError(array $data, int $httpStatus): array
    {
        $reason = (string) ($data['reason'] ?? $data['detail'] ?? 'Unknown Fonnte error');
        $reasonLower = strtolower($reason);

        // Check permanent error conditions
        $isPermanent = false;

        if (Str::contains($reasonLower, ['token'])) {
            $isPermanent = true;
        } elseif (Str::contains($reasonLower, ['device', 'disconnect'])) {
            $isPermanent = true;
        } elseif (Str::contains($reasonLower, ['number', 'invalid'])) {
            $isPermanent = true;
        }

        if ($httpStatus >= 500) {
            $isPermanent = false;
        }

        return [
            'success'   => false,
            'wamid'     => null,
            'error'     => "Fonnte Error ({$httpStatus}): {$reason}",
            'permanent' => $isPermanent,
        ];
    }
}
