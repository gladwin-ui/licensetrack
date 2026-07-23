<?php

namespace App\Services\WhatsApp;

interface WhatsAppGateway
{
    /**
     * Send a WhatsApp template message.
     *
     * @param  string  $phone        Recipient phone number (normalized: 628xxxxxxxxx)
     * @param  string  $templateName WhatsApp message template name
     * @param  array   $params       Template parameter values in order
     * @param  string  $body         Free text body (used by Fonnte, empty for Meta)
     * @return array{success: bool, wamid: ?string, error: ?string, permanent: bool}
     */
    public function send(string $phone, string $templateName, array $params, string $body = ''): array;
}
