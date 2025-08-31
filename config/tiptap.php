<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Extensions
    |--------------------------------------------------------------------------
    |
    | Default extensions to be loaded when creating a new Tiptap instance.
    | Available extensions: StarterKit, Color, FontFamily, TextAlign
    |
    */
    'extensions' => [
        // Use StarterKit for basic functionality
        \Tiptap\Extensions\StarterKit::class => [],

        // Additional extensions (uncomment as needed)
        // \Tiptap\Extensions\Color::class => [],
        // \Tiptap\Extensions\FontFamily::class => [],
        // \Tiptap\Extensions\TextAlign::class => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for parsed content to improve performance.
    |
    */
    'cache' => [
        'enabled' => env('TIPTAP_CACHE_ENABLED', true),
        'store' => env('TIPTAP_CACHE_STORE', null), // null uses default cache store
        'ttl' => env('TIPTAP_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'tiptap',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for content validation
    |
    */
    'validation' => [
        'max_length' => 50000,
        'max_depth' => 10,
        'allowed_tags' => null, // null means all configured extensions are allowed
    ],
];
