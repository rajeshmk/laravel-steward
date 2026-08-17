<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Sort Direction
    |--------------------------------------------------------------------------
    |
    | Default sort direction when no sort parameter is specified.
    | Options: 'asc', 'desc'
    |
    */
    'default_sort_direction' => 'desc',

    /*
    |--------------------------------------------------------------------------
    | Default page size
    |--------------------------------------------------------------------------
    |
    | Default number of items per page when pagination is requested.
    |
    */
    'default_page_size' => 15,

    /*
    |--------------------------------------------------------------------------
    | Maximum page size
    |--------------------------------------------------------------------------
    |
    | Maximum allowed page size to prevent excessive load.
    |
    */
    'max_page_size' => 100,

    // Request-complexity limits. Keep these finite to bound database work.
    'max_page_number' => 10000,
    'max_filter_fields' => 20,
    'max_filter_values' => 100,
    'max_includes' => 20,
    'max_fieldsets' => 20,
    'max_fields' => 100,
    'max_sort_fields' => 20,
    'max_value_length' => 2048,

    /*
    |--------------------------------------------------------------------------
    | API Response & Request Format Configuration
    |--------------------------------------------------------------------------
    |
    | 'default_format'           - Default format when not specified ("rest" or "jsonapi").
    | 'enforce_request_format'   - Incoming payload policy: "any" (hybrid), "jsonapi", or "rest".
    | 'allow_format_override'    - Whether ?format= parameter / Accept header can override default.
    | 'query_parameter'          - The query string parameter key for format overrides.
    |
    */
    'api' => [
        'default_format' => env('STEWARD_API_FORMAT', 'rest'),
        'enforce_request_format' => env('STEWARD_ENFORCE_REQUEST_FORMAT', 'any'),
        'allow_format_override' => (bool) env('STEWARD_ALLOW_FORMAT_OVERRIDE', true),
        'query_parameter' => 'format',
    ],
];
