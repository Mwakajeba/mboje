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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'kilakona'),
        // Kilakona (primary — use SMS_* in .env)
        'senderid' => env('SMS_SENDERID', env('KILAKONA_SENDER_ID', env('BEEM_SENDER_ID', 'SAFCO'))),
        'url' => env('SMS_URL', env('KILAKONA_SMS_URL', 'https://messaging.kilakona.co.tz/api/v1/vendor/message/send')),
        'api_key' => env('SMS_API_KEY', env('KILAKONA_API_KEY')),
        'api_secret' => env('SMS_API_SECRET', env('KILAKONA_API_SECRET')),
        'callback_url' => env('SMS_CALLBACK_URL', env('KILAKONA_CALLBACK_URL')),
        // Beem Africa (when SMS_PROVIDER=beem)
        'token' => env('SMS_TOKEN', env('BEEM_SECRET_KEY')),
        'key' => env('SMS_KEY', env('BEEM_API_KEY')),
    ],

    'beem' => [
        'api_key' => env('BEEM_API_KEY'),
        'secret_key' => env('BEEM_SECRET_KEY'),
        'sender_id' => env('BEEM_SENDER_ID', 'SAFCO'),
    ],

];
