<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | The storage disk to use for saving optimized images.
    | Defaults to 'public' as used across the existing ecommerce project.
    |
    */
    'disk' => env('IMAGE_OPTIMIZATION_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Image Processing Driver
    |--------------------------------------------------------------------------
    |
    | The driver used by Intervention Image. Supported options: 'gd', 'imagick'.
    |
    */
    'driver' => env('IMAGE_OPTIMIZATION_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Output Format & Quality
    |--------------------------------------------------------------------------
    |
    | Target output format and compression quality (1 - 100).
    | Default target format is 'webp' and default quality is 80.
    |
    */
    'format' => env('IMAGE_OPTIMIZATION_FORMAT', 'webp'),
    'quality' => (int) env('IMAGE_OPTIMIZATION_QUALITY', 90),

    /*
    |--------------------------------------------------------------------------
    | Maximum Allowed Upload Size (in Kilobytes)
    |--------------------------------------------------------------------------
    |
    | Maximum upload size before processing. Default is 10 MB (10240 KB).
    |
    */
    'max_file_size' => (int) env('IMAGE_OPTIMIZATION_MAX_SIZE', 20480),

    /*
    |--------------------------------------------------------------------------
    | Supported Input MIME Types
    |--------------------------------------------------------------------------
    |
    | List of allowed input MIME types for conversion.
    |
    */
    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
        'image/jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Presets / Maximum Dimensions (Pixels)
    |--------------------------------------------------------------------------
    |
    | Contextual maximum dimensions for proportional resizing.
    | Images smaller than these limits will NOT be enlarged or upscaled.
    | Aspect ratio and composition are always preserved without stretching.
    |
    */
    'presets' => [
        'product' => [
            'max_width' => (int) env('IMAGE_PRODUCT_MAX_WIDTH', 1200),
            'max_height' => (int) env('IMAGE_PRODUCT_MAX_HEIGHT', 1200),
        ],
        'product_thumbnail' => [
            'max_width' => (int) env('IMAGE_PRODUCT_THUMBNAIL_MAX_WIDTH', 500),
            'max_height' => (int) env('IMAGE_PRODUCT_THUMBNAIL_MAX_HEIGHT', 500),
        ],
        'category' => [
            'max_width' => (int) env('IMAGE_CATEGORY_MAX_WIDTH', 800),
            'max_height' => (int) env('IMAGE_CATEGORY_MAX_HEIGHT', 800),
        ],
        'banner' => [
            'max_width' => (int) env('IMAGE_BANNER_MAX_WIDTH', 1920),
            'max_height' => (int) env('IMAGE_BANNER_MAX_HEIGHT', 1080),
        ],
        'brand' => [
            'max_width' => (int) env('IMAGE_BRAND_MAX_WIDTH', 600),
            'max_height' => (int) env('IMAGE_BRAND_MAX_HEIGHT', 600),
        ],
        'setting' => [
            'max_width' => (int) env('IMAGE_SETTING_MAX_WIDTH', 800),
            'max_height' => (int) env('IMAGE_SETTING_MAX_HEIGHT', 800),
        ],
        'default' => [
            'max_width' => 1200,
            'max_height' => 1200,
        ],
    ],
];
