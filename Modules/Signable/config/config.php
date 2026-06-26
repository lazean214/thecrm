<?php

return [
    'name' => 'Signable',

    'api' => [
        'server' => env('SIGNABLE_API_SERVER', 'https://api.signable.co.uk/v1'),
        'key' => env('SIGNABLE_API_KEY'),
        'secret' => env('SIGNABLE_API_SECRET', 'x'),
        'timeout' => (int) env('SIGNABLE_API_TIMEOUT', 30),
    ],

    'webhook' => [
        // Secret for verifying incoming webhook signatures
        'secret' => env('SIGNABLE_WEBHOOK_SECRET'),
    ],
];
