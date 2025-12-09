<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your API. This will be used as a variable in Postman.
    |
    */
    'base_url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Collection Name
    |--------------------------------------------------------------------------
    |
    | The name of the Postman collection that will be generated.
    |
    */
    'collection_name' => env('APP_NAME', 'Laravel API') . ' Collection',

    /*
    |--------------------------------------------------------------------------
    | Collection Description
    |--------------------------------------------------------------------------
    |
    | Description for the Postman collection.
    |
    */
    'collection_description' => 'Auto-generated Postman collection from Laravel routes',

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | The path where the generated Postman collection JSON file will be saved.
    |
    */
    'output_path' => storage_path('app/postman-collection.json'),

    /*
    |--------------------------------------------------------------------------
    | Include Routes
    |--------------------------------------------------------------------------
    |
    | Route groups to include. Available options: 'web', 'api', 'all'
    |
    */
    'include_routes' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Exclude Routes
    |--------------------------------------------------------------------------
    |
    | Route patterns to exclude from the collection.
    |
    */
    'exclude_routes' => [
        'telescope',
        'horizon',
        'ignition',
        '_debugbar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Postman API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for updating Postman collections via API.
    |
    */
    'postman' => [
        'api_key' => env('POSTMAN_API_KEY'),
        'workspace_id' => env('POSTMAN_WORKSPACE_ID'),
        'collection_id' => env('POSTMAN_COLLECTION_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Authentication
    |--------------------------------------------------------------------------
    |
    | Whether to include authentication examples in the collection.
    |
    */
    'enable_auth' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Headers
    |--------------------------------------------------------------------------
    |
    | Default headers to include in all requests.
    |
    */
    'default_headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
];
