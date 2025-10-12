<?php

return [
    'paths' => ['api/*', 'audio/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL_LOCAL', 'http://localhost:3000'),
        env('FRONTEND_URL_PROD',  'https://elzaai.com'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
