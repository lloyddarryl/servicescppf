<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'http://localhost:8000','http://127.0.0.1:3000','http://localhost:3001',
 'https://*.cppf-services.com','https://servicescppf.vercel.app',
],

    'allowed_origins_patterns' => ['/^https:\/\/.+\.cppf-services\.com$/'],

    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
