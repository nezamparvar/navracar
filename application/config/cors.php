<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['POST', 'OPTIONS'],
    'allowed_origins' => [
        'https://localhost',
        'capacitor://localhost',
    ],
    'allowed_origins_patterns' => [
        '#^chrome-extension://[a-p]{32}$#',
    ],
    'allowed_headers' => ['Content-Type', 'Accept', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
