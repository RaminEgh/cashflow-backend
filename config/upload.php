<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | The maximum file size in kilobytes that can be uploaded.
    | Default: 10240 KB (10 MB)
    |
    */

    'max_file_size' => env('UPLOAD_MAX_FILE_SIZE', 10240),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | Define allowed file types for uploads. You can specify mime types
    | or file extensions. Leave empty to allow all file types.
    |
    */

    'allowed_types' => [
        'images' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'documents' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | The default disk to use for file uploads.
    |
    */

    'default_disk' => env('UPLOAD_DEFAULT_DISK', 'public_uploads'),

    /*
    |--------------------------------------------------------------------------
    | Private Disk
    |--------------------------------------------------------------------------
    |
    | The disk to use for private file uploads.
    |
    */

    'private_disk' => env('UPLOAD_PRIVATE_DISK', 'private_uploads'),

];
