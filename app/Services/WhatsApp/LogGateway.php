<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogGateway implements WhatsAppGateway
{
    /**
     * Log the WhatsApp message instead of actually sending it.
     * Used during development (WA_GATEWAY=log).
     *
     * @return array{success: bool, wamid: ?string, error: ?string, permanent: bool}
     */
    public function send(string $phone, string $templateName, array $params, string $body = ''): array
    {
        $wamid = 'log-' . Str::uuid()->toString();

        Log::channel('whatsapp')->info('WhatsApp message (stub — not actually sent)', [
            'to'           => $phone,
            'template'     => $templateName,
            'params'       => $params,
            'body'         => $body,
            'wamid'        => $wamid,
            'timestamp'    => now()->toIso8601String(),
        ]);

        return [
            'success'   => true,
            'wamid'     => $wamid,
            'error'     => null,
            'permanent' => false,
        ];
    }
}
