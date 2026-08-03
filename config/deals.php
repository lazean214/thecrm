<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kanban Settings
    |--------------------------------------------------------------------------
    */

    'kanban' => [
        'per_stage' => (int) env('DEAL_KANBAN_PER_STAGE', 50),
        'cache_ttl' => (int) env('DEAL_KANBAN_CACHE_TTL', 120),
        'max_cache_age_ms' => (int) env('DEAL_KANBAN_MAX_CACHE_AGE_MS', 900000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stage Configuration
    |--------------------------------------------------------------------------
    */

    'stages' => [
        'doc sent' => [
            'label' => 'Doc Sent',
            'icon' => '📄',
            'color' => '#4f46e5',
        ],
        'doc signed' => [
            'label' => 'Doc Signed',
            'icon' => '✍️',
            'color' => '#0891b2',
        ],
        'compliant' => [
            'label' => 'Compliant',
            'icon' => '✅',
            'color' => '#54ff54',
        ],
        'ready for payment' => [
            'label' => 'Ready for Payment',
            'icon' => '💳',
            'color' => '#ea580c',
        ],
        'paid' => [
            'label' => 'Paid',
            'icon' => '💰',
            'color' => '#16a34a',
        ],
        'lost' => [
            'label' => 'Lost',
            'icon' => '❌',
            'color' => '#dc2626',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Stage
    |--------------------------------------------------------------------------
    */

    'default_stage' => 'doc sent',

];
