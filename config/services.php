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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'apis_net_pe' => [
        'url' => env('APIS_NET_PE_URL', 'https://api.apis.net.pe/v1/dni'),
        'referer' => env('APIS_NET_PE_REFERER', 'https://apis.net.pe/consulta-dni-api'),
        'token' => env('APIS_NET_PE_TOKEN'),
    ],

    'geografia' => [
        'paises_url' => env('GEOGRAFIA_PAISES_URL', 'https://raw.githubusercontent.com/umpirsky/country-list/master/data/es/country.json'),
        'ubigeos_url' => env('GEOGRAFIA_UBIGEOS_URL', 'https://free.e-api.net.pe/ubigeos.json'),
    ],

];
