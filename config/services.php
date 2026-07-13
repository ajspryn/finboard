<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'absensi' => [
        'url' => env('ABSENSI_API_URL', 'https://absensi.bprsbtb.co.id/api/daily-activities'),
        'token' => env('ABSENSI_API_TOKEN'),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'meta'),
        'from-phone-number-id' => env('WHATSAPP_FROM_PHONE_NUMBER_ID'),
        'token' => env('WHATSAPP_TOKEN'),
        'otp_template_name' => env('WHATSAPP_OTP_TEMPLATE_NAME', 'finboard_otp_code'),
        'otp_template_language' => env('WHATSAPP_OTP_TEMPLATE_LANGUAGE', 'id'),
        'otp_message_template' => env('WHATSAPP_OTP_MESSAGE_TEMPLATE', 'Kode OTP Anda: :otp. Berlaku :minutes menit.'),
        'otp_expired_in_minutes' => env('PIN_EXPIRES_IN_MINUTES', 10),
        'fonte' => [
            'token' => env('WHATSAPP_FONTE_TOKEN', env('FONTE_TOKEN')),
            'endpoint' => env('WHATSAPP_FONTE_ENDPOINT', 'https://api.fonnte.com/send'),
        ],
    ],

];
