<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        'https://localhost',
        'capacitor://localhost',
    ],
    'allowed_origins_patterns' => [
        '#^chrome-extension://[a-p]{32}$#',
    ],
    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Navracar-Installation',
        'X-Navracar-Installation-Secret',
    ],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
