<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "log", "meta", "fonnte"
    | - log    : hanya menulis ke storage/logs/whatsapp.log (untuk development)
    | - meta   : mengirim via Meta WhatsApp Cloud API (Fase 3)
    | - fonnte : mengirim via Fonnte WhatsApp Gateway
    |
    */

    'gateway' => env('WA_GATEWAY', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Fonnte Gateway Credentials
    |--------------------------------------------------------------------------
    */

    'fonnte_token' => env('FONNTE_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API Credentials
    |--------------------------------------------------------------------------
    */

    'api_version'      => env('WA_API_VERSION', 'v21.0'),
    'phone_number_id'  => env('WA_PHONE_NUMBER_ID'),
    'access_token'     => env('WA_ACCESS_TOKEN'),
    'template_lang'    => env('WA_TEMPLATE_LANG', 'id'),
    'templates' => [
        'reminder' => env('WA_TEMPLATE_REMINDER', 'license_expiry_reminder'),
        'expired'  => env('WA_TEMPLATE_EXPIRED', 'license_expired_alert'),
    ],

];

