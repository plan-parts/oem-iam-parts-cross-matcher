<?php

return [

    /*
    |--------------------------------------------------------------------------
    | plan.parts API Token
    |--------------------------------------------------------------------------
    |
    | Your personal API token from https://plan.parts
    | Register at https://plan.parts to get your token.
    | Each analog/cross search costs 2 tokens per request.
    |
    */

    'token' => env('PLAN_PARTS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    */

    'base_url' => env('PLAN_PARTS_BASE_URL', 'https://plan.parts/api'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'timeout' => env('PLAN_PARTS_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix & Middleware
    |--------------------------------------------------------------------------
    |
    | The web interface will be available at /{prefix}
    | Default: /cross-matcher
    |
    */

    'route_prefix' => env('PLAN_PARTS_ROUTE_PREFIX', 'cross-matcher'),

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Price List Upload Settings
    |--------------------------------------------------------------------------
    */

    'upload' => [
        'disk'       => env('PLAN_PARTS_DISK', 'local'),
        'max_size_mb' => 20,
        'allowed_extensions' => ['csv'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Behavior
    |--------------------------------------------------------------------------
    |
    | max_analog_depth: how many levels of analogs to follow (1 = direct only)
    | skip_brand_lookup: set true to skip brand auto-detection step
    |
    */

    'search' => [
        'max_analog_depth'  => 1,
        'skip_brand_lookup' => false,
    ],

];
