<?php

return [
    'prefix' => 'odata',
    'middleware' => [],
    'readonly' => true,
    'authorization' => false,
    'namespace' => 'com.example.odata',
    'disk' => 'local',
    'async' => [],
    'discovery' => [
        'blacklist' => ['password', 'api_key', 'api_token', 'api_secret', 'secret']
    ]
];
