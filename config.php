<?php

return [
    /**
     * The route prefix to use, by default mounted at http://localhost:8080/odata but can be moved and renamed as required.
     */
    'prefix' => env('LODATA_PREFIX', 'odata'),

    /*
     * An array of middleware to be included when processing an OData request. Common middleware used would be to handle JWT authentication, or adding CORS headers.
     */
    'middleware' => [],

    /*
     * Whether this service should allow data modification requests. Set to true by default just for safety.
     */
    'readonly' => true,

    /*
     * Set this to true if you want to use Laravel authorization gates for your OData requests.
     */
    'authorization' => false,

    /*
     * Whether to use streaming JSON responses by default.
     * @link https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_PayloadOrderingConstraints
     */
    'streaming' => true,

    /*
     * This is an OData concept to group your data model according to a globally unique namespace. Some clients may use this information for display purposes.
     */
    'namespace' => env('LODATA_NAMESPACE', 'com.example.odata'),

    /*
     * The name of the Laravel disk to use to store asynchronously processed requests.
     * In a multi-server shared hosting environment, all hosts should be able to access this disk
     */
    'disk' => env('LODATA_DISK', 'local'),

    /*
     * Configuration relating to asynchronous request processing.
     */
    'async' => [
        /*
         * Set the desired queue for the job.
         */
        'queue' => env('LODATA_ASYNC_QUEUE'),

        /*
         * Set the desired connection for the job.
         */
        'connection' => env('LODATA_ASYNC_CONNECTION'),
    ],

    /*
     * Configuration relating to auto-discovery
     */
    'discovery' => [
        /*
         * The blacklist of property names that will not be added during auto-discovery
         */
        'blacklist' => [
            'password',
            'api_key',
            'api_token',
            'api_secret',
            'secret',
        ]
    ]
];
