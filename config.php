<?php

return [
    'prefix' => 'odata',
    'middleware' => ['auth.basic'],
    'namespace' => 'com.example.odata',
    'disk' => 'local',
    'discovery' => [
        'blacklist' => ['password']
    ]
];
