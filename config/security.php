<?php

return [
    'access' => [
        'active_profile_states' => array_filter(array_map(
            'trim',
            explode(',', (string) env('MLP_ACTIVE_PROFILE_STATES', ''))
        )),
    ],

    'rate_limits' => [
        'livewire_updates_per_minute' => (int) env('MLP_RATE_LIMIT_LIVEWIRE', 120),
        'file_uploads_per_minute' => (int) env('MLP_RATE_LIMIT_UPLOADS', 20),
        'forms_per_minute' => (int) env('MLP_RATE_LIMIT_FORMS', 30),
        'documents_per_minute' => (int) env('MLP_RATE_LIMIT_DOCUMENTS', 60),
        'labels_per_minute' => (int) env('MLP_RATE_LIMIT_LABELS', 30),
    ],

    'pii' => [
        'mask_in_tables' => env('MLP_MASK_PII_TABLES', true),
    ],
];
