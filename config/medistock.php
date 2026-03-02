<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Scan Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI-powered invoice scanning feature.
    | Supports 'llm' (Anthropic Claude) and 'google_vision' drivers.
    |
    */

    'invoice_scan' => [
        'parser_driver' => env('INVOICE_PARSER_DRIVER', 'llm'),
        'daily_limit' => (int) env('INVOICE_SCAN_DAILY_LIMIT', 50),
        'monthly_limit' => (int) env('INVOICE_SCAN_MONTHLY_LIMIT', 500),
        'max_image_size' => (int) env('INVOICE_SCAN_MAX_IMAGE_SIZE', 10240), // KB
        'retention_days' => (int) env('INVOICE_SCAN_RETENTION_DAYS', 90),
        'allowed_mimetypes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Settings
    |--------------------------------------------------------------------------
    |
    | Controls how inventory is managed across all tenants.
    |
    */

    'inventory' => [
        'prevent_negative_stock' => env('MEDISTOCK_PREVENT_NEGATIVE_STOCK', true),
        'low_stock_threshold' => (int) env('MEDISTOCK_LOW_STOCK_THRESHOLD', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Near Expiry Thresholds (in days)
    |--------------------------------------------------------------------------
    |
    | Define thresholds for near-expiry warnings. Batches within these
    | day ranges will be flagged accordingly in reports and alerts.
    |
    */

    'near_expiry_days' => [
        'critical' => 30,
        'warning' => 60,
        'notice' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dead Stock Threshold (in days)
    |--------------------------------------------------------------------------
    |
    | Items with no sales movement for this many days will be
    | classified as dead stock in inventory reports.
    |
    */

    'dead_stock_days' => (int) env('MEDISTOCK_DEAD_STOCK_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination size for list endpoints and views.
    |
    */

    'pagination' => [
        'default' => (int) env('MEDISTOCK_PAGINATION_DEFAULT', 25),
        'max' => (int) env('MEDISTOCK_PAGINATION_MAX', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | GST Settings
    |--------------------------------------------------------------------------
    |
    | Default GST configuration for Indian pharmacy operations.
    |
    */

    'gst' => [
        'rates' => [0, 5, 12, 18, 28],
        'default_rate' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => [
        'code' => 'INR',
        'symbol' => '₹',
        'decimal_places' => 2,
    ],

];
