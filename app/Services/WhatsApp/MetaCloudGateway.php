<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCloudGateway implements WhatsAppGateway
{
    /**
     * Send a template message via Meta WhatsApp Cloud API.
     *
     * @param string $to
     * @param string $template
     * @param array $parameters
     * @param string $body
     * @return array{success: bool, wamid: ?string, error: ?string, permanent: bool}
     */
    public function send(string $to, string $template, array $parameters, string $body = ''): array
    {
        $apiVersion = config('whatsapp.api_version');
        $phoneNumberId = config('whatsapp.phone_number_id');
        $accessToken = config('whatsapp.access_token');
        $lang = config('whatsapp.template_lang', 'id');

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        // Build parameters array for Meta API
        $components = [];
        if (!empty($parameters)) {
            $paramObjects = array_map(function ($param) {
                return [
                    'type' => 'text',
                    'text' => (string) $param,
                ];
            }, $parameters);

            $components[] = [
                'type'       => 'body',
                'parameters' => $paramObjects,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $template,
                'language'   => ['code' => $lang],
                'components' => $components,
            ],
        ];

        // Log request (masking access token if we logged headers, but Http client doesn't log headers by default, 
        // we'll log payload manually)
        Log::channel('whatsapp')->info("MetaCloudGateway Request", [
            'url'        => $url,
            'to'         => $to,
            'template'   => $template,
            'parameters' => $parameters,
        ]);

        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->connectTimeout(5)
                ->post($url, $payload);

            Log::channel('whatsapp')->info("MetaCloudGateway Response", [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $wamid = $data['messages'][0]['id'] ?? null;
                
                return [
                    'success'   => true,
                    'wamid'     => $wamid,
                    'error'     => null,
                    'permanent' => false,
                ];
            }

            // Handle Error
            $errorData = $response->json('error');
            return $this->handleError($errorData, $response->status());

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error("MetaCloudGateway Exception", [
                'message' => $e->getMessage(),
            ]);

            return [
                'success'   => false,
                'wamid'     => null,
                'error'     => "Connection error: " . $e->getMessage(),
                'permanent' => false, // Connection errors are temporary
            ];
        }
    }

    /**
     * Parse Meta error to determine if it's permanent and return user-friendly message.
     */
    private function handleError(?array $errorData, int $httpStatus): array
    {
        if (!$errorData) {
            return [
                'success'   => false,
                'wamid'     => null,
                'error'     => "Unknown HTTP error ({$httpStatus})",
                'permanent' => $httpStatus < 500, // Usually 4xx are permanent, 5xx temporary, but we fallback
            ];
        }

        $code = $errorData['code'] ?? null;
        $message = $errorData['message'] ?? 'Unknown error';

        $permanentCodes = [
            190    => "Access token tidak valid atau sudah kedaluwarsa. Buat System User token yang permanen.",
            131030 => "Nomor ini belum didaftarkan di allowed list test number Meta (maks. 5 nomor).",
            132001 => "Template belum ada atau belum disetujui Meta. Cek nama & bahasa template.",
            132000 => "Jumlah parameter tidak sesuai definisi template.",
            131026 => "Nomor tujuan tidak terdaftar di WhatsApp.",
            100    => "Request tidak valid. Cek Phone Number ID.",
        ];

        if (array_key_exists($code, $permanentCodes)) {
            return [
                'success'   => false,
                'wamid'     => null,
                'error'     => "Meta Error {$code}: " . $permanentCodes[$code],
                'permanent' => true,
            ];
        }

        // Specific temporary codes mentioned in PRD
        $temporaryCodes = [130429, 80007, 131000];
        
        if (in_array($code, $temporaryCodes) || $httpStatus >= 500) {
            return [
                'success'   => false,
                'wamid'     => null,
                'error'     => "Meta Error {$code}: {$message} (Temporary)",
                'permanent' => false,
            ];
        }

        // Unknown code, treat as temporary and log it so admin can see
        return [
            'success'   => false,
            'wamid'     => null,
            'error'     => "Meta Error {$code}: {$message} (Unknown code, treated as temporary)",
            'permanent' => false,
        ];
    }
}
