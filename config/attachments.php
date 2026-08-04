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

    /*
    |--------------------------------------------------------------------------
    | Prune retention (days)
    |--------------------------------------------------------------------------
    |
    | Default age (in days) used by `php artisan attachments:prune` to decide
    | which soft-deleted files/images to permanently delete (rows + physical
    | files). Override per-run with `--days=`.
    |
    */
    'prune_days' => (int) env('ATTACHMENTS_PRUNE_DAYS', 30),
];
