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
    | Authorization abilities
    |--------------------------------------------------------------------------
    |
    | The routes authorize every attachment against the model it belongs to
    | (the `fileable` / `imageable` parent) through Laravel's Gate:
    |
    |   - 'view'   -> downloading a file
    |   - 'update' -> deleting or reordering files / images
    |
    | The parent model therefore needs a policy with these abilities. A parent
    | without a policy, or an attachment whose parent no longer exists, is
    | denied with a 403. Rename the abilities here if your policies use other
    | names.
    |
    */
    'abilities' => [
        'view'   => 'view',
        'update' => 'update',
    ],

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
