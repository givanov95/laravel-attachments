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
    | Extra file extensions (DANGEROUS — opt in explicitly)
    |--------------------------------------------------------------------------
    |
    | Uploads keep their detected extension only when it is on the package's
    | safe list (common images, office documents, archives, audio and video);
    | everything else is stored as `.bin`. List more extensions here, e.g.
    | ['svg', 'xml'], to keep them as well.
    |
    | WARNING: files are served straight from the storage disk. On a public
    | disk an uploaded `html`, `htm`, `xhtml`, `svg`, `xml` or `js` file runs
    | scripts on your app's domain (stored XSS: session theft, actions as the
    | logged-in user), and `php`, `phtml`, `cgi` and similar may be executed
    | by the web server. Only add such extensions when every uploader is
    | trusted, or when the disk is not publicly served / the web server sends
    | these files as downloads (`Content-Disposition: attachment`).
    |
    */
    'extra_extensions' => [],

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
