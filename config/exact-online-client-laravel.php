<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exact Online base uri
    |--------------------------------------------------------------------------
    |
    */

    'base_uri' => env('EXACT_ONLINE_CLIENT_BASE_URI', 'https://start.exactonline.nl'),

    /*
    |--------------------------------------------------------------------------
    | Exact Online base uri
    |--------------------------------------------------------------------------
    |
    */

    'api_path' => env('EXACT_ONLINE_CLIENT_API_PATH', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Exact Online client ID
    |--------------------------------------------------------------------------
    |
    */

    'client_id' => env('EXACT_ONLINE_CLIENT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Exact Online client secret
    |--------------------------------------------------------------------------
    |
    */

    'client_secret' => env('EXACT_ONLINE_CLIENT_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Exact Online Webhook secret
    |--------------------------------------------------------------------------
    |
    */

    'webhook_secret' => env('EXACT_ONLINE_WEBHOOK_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Exact Online Webhook secret
    |--------------------------------------------------------------------------
    |
    */

    'division' => env('EXACT_ONLINE_DIVISION', null),
];
