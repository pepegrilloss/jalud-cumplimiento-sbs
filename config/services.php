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

    'labelary' => [
        'enabled' => env('LABELARY_ENABLED', true),
        'base_url' => rtrim(env('LABELARY_BASE_URL', 'https://api.labelary.com'), '/'),
        'timeout' => (int) env('LABELARY_TIMEOUT', 8),
    ],

    'dni_ocr' => [
        'enabled' => env('DNI_OCR_ENABLED', false),
        'tesseract_path' => env('TESSERACT_PATH', 'tesseract'),
        'language' => env('TESSERACT_LANGUAGE', 'spa'),
        'pass_timeout' => (int) env('TESSERACT_PASS_TIMEOUT', env('TESSERACT_TIMEOUT', 8)),
        'dpi' => (int) env('TESSERACT_DPI', 300),
        'tessdata_path' => env('TESSDATA_PREFIX') ?: storage_path('app/ocr/tessdata'),
    ],

    'meta_whatsapp' => [
        'phone_number_id' => env('META_PHONE_NUMBER_ID'),
        'business_account_id' => env('META_BUSINESS_ACCOUNT_ID'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('META_APP_SECRET'),
        'template' => env('META_WHATSAPP_TEMPLATE', 'nueva_solicitud_visita'),
        'template_next_approval' => env('META_WHATSAPP_TEMPLATE_NEXT_APPROVAL', 'siguiente_area_aprobacion'),
        'template_approval_reminder' => env('META_WHATSAPP_TEMPLATE_APPROVAL_REMINDER', 'recordatorio_aprobacion_3h'),
        'language' => env('META_WHATSAPP_LANGUAGE', 'es_PE'),
        'admin_phone' => env('ADMIN_WHATSAPP'),
        'timeout' => (int) env('META_WHATSAPP_TIMEOUT', 10),
    ],

];
