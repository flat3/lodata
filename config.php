<?php

return [
    'route' => 'odata',
    'middleware' => ['auth.basic'],
    'namespace' => 'com.example.odata',
    'disk' => 'local',
];
