<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    |
    | The filesystems disk used for storing uploaded files / images. Must be
    | one of the disks defined in config/filesystems.php. Defaults to 'public'.
    |
    */
    'disk' => env('ATTACHMENTS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Route middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the package's image/file routes (destroy, order,
    | download). Override to add 'verified', roles, custom guards, etc.
    |
    */
    'middleware' => ['web', 'auth'],
];
