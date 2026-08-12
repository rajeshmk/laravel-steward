<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default sort column
    |--------------------------------------------------------------------------
    |
    | Column used for default ordering when no sort is specified in the request.
    | Set to null to use the model's primary key.
    |
    */
    'default_sort_column' => null,

    /*
    |--------------------------------------------------------------------------
    | Default sort direction
    |--------------------------------------------------------------------------
    |
    | Direction for default ordering: 'asc' or 'desc'.
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
    'max_page_number' => 10_000,
    'max_filter_fields' => 20,
    'max_filter_values' => 100,
    'max_includes' => 20,
    'max_fields' => 100,
    'max_sort_fields' => 20,
    'max_value_length' => 2_048,
];
