# givanov95/laravel-attachments

Polymorphic file & image attachments for Laravel.

## What's included

- `Image` + `File` Eloquent models with appended `url` accessor (uses `Storage::disk()->url()`)
- `HasImages` + `HasFiles` traits:
    - `setImages($collection, 'section')` / `setFiles($collection, 'section')` — staged + persisted in `saved()` hook
    - Multi-section support, accumulates across calls
    - Optional `profileImageColumn()` override that caches the first image's path on a column on the parent model (uses `saveQuietly()` to avoid recursion)
    - `deleting()` hook cascades attachment delete
- `UploadHelper::uploadMultipleImages($request, 'images', 'subdir')` — moves UploadedFile[] to disk, returns ready-to-stage models
- `FileStr::generateUniqueFileName($uploadedFile)` — collision-safe filenames
- Routes (auto-registered): `images.destroy`, `images.order`, `files.destroy`, `files.download`, `files.order` (id-based)

## Install

```bash
composer require givanov95/laravel-attachments
php artisan migrate
php artisan storage:link
# optional:
php artisan vendor:publish --tag=attachments-config
```

## Configuration

Defaults in `config/attachments.php` (publishable):

```php
return [
    'disk' => env('ATTACHMENTS_DISK', 'public'),
    'middleware' => ['web', 'auth'],
    'abilities' => ['view' => 'view', 'update' => 'update'],
];
```

Override `middleware` to add `'verified'`, role guards, custom guards, etc. The image/file routes are registered under this middleware stack. `abilities` maps the two policy abilities the routes check (see [Authorization](#authorization)).

## Usage

### On a model

```php
use Givanov95\LaravelAttachments\Concerns\HasImages;
use Givanov95\LaravelAttachments\Concerns\HasFiles;

class Product extends Model
{
    use HasImages, HasFiles;

    // optional — cache first image path on this column for fast listing queries
    // optional — cache first image path on this column for fast listing queries
    protected function profileImageColumn(): ?string
    {
        return 'image_path';
    }
}
```

### In a controller

```php
use Givanov95\LaravelAttachments\Services\UploadHelper;

public function store(StoreProductRequest $request)
{
    $product = new Product($request->validated());
    $product->save();

    $product
        ->setImages(
            UploadHelper::uploadMultipleImages($request->validated(), 'images', 'products'),
            'default'
        )
        ->setImages(
            UploadHelper::uploadMultipleImages($request->validated(), 'size_chart', 'products/charts'),
            'size_chart'
        )
        ->setFiles(
            UploadHelper::uploadMultipleFiles($request->validated(), 'specs', 'products/specs'),
            'specs'
        )
        ->save();

    return back();
}
```

### Reading attachments

```php
$product->images;                // ordered by `order` ASC
$product->files;
$product->firstImage;            // most-min order
$product->latestImage;           // most-recent created
$product->getGroupedImages(['default', 'size_chart']);
```

### URL access

Frontend never constructs paths — the model exposes `url`:

```vue
<img :src="image.url" />
```

## Routes

Auto-registered (id-based, behind `['web', 'auth']` by default):

| Method | Path | Name |
|---|---|---|
| DELETE | `/images/{image}` | `images.destroy` |
| PUT | `/images/order` | `images.order` |
| DELETE | `/files/{file}` | `files.destroy` |
| PUT | `/files/order` | `files.order` |
| GET | `/files/{file}/download` | `files.download` |

The `order` endpoints accept `{ orderArray: [id, id, ...] }` and rewrite the `order` column to the array's position (1-indexed).

## Authorization

`auth` only proves the visitor is logged in — it says nothing about *whose* attachment they are touching. Since v1.3.0 every route authorizes the attachment against the model it hangs off (`fileable` / `imageable`) through Laravel's Gate:

| Route | Ability checked on the parent model |
|---|---|
| `files.download` | `view` |
| `images.destroy`, `files.destroy` | `update` |
| `images.order`, `files.order` | `update` (for **every** parent referenced in `orderArray`; one foreign id rejects the whole request) |

So each model that uses `HasImages` / `HasFiles` needs a policy with these abilities:

```php
class ProjectPolicy
{
    public function view(User $user, Project $project): bool { /* ... */ }

    public function update(User $user, Project $project): bool { /* ... */ }
}
```

The check **fails closed**: a parent model without a policy, or an attachment whose parent no longer exists, gets a `403`. If your policies use other ability names, remap them in `config/attachments.php` under `abilities`.

> **Upgrading from < 1.3.0:** add the policies above *before* updating, otherwise the routes will answer `403` for every user. Versions up to 1.2.0 let any authenticated user delete, reorder or download other users' attachments (GHSA-6vf2-w3m8-6jqm) — update as soon as the policies are in place.

### Stored file names

`UploadHelper` stores uploads as `<sanitized-stem>_<time>_<uniqid>.<ext>`. The extension is derived from the detected content type (never from the client-supplied name) and executable extensions such as `php`, `phtml` or `sh` are stored as `.bin`. The client's original name is kept in `original_name` and used for downloads. You should still validate uploads in your own requests.

## Development

```bash
composer install
composer test          # PHPUnit
composer analyse       # PHPStan level 5
```

### Pre-commit hook

`composer install` / `composer update` installs a `pre-commit` hook into `.git/hooks/` via the [`givanov95/laravel-git-hooks`](https://github.com/givanov95/laravel-git-hooks) plugin (the hook itself lives in `vendor/`). Before each commit it runs php-cs-fixer on staged files (when configured), blocks leftover `dd(` / `dump(` calls and runs the test suite.

It is a fast-feedback gate only. Skip it for one commit with `SKIP_HOOK=1 git commit ...` (or `git commit --no-verify`).

### CI

GitHub Actions (`.github/workflows/ci.yml`) runs the tests and PHPStan on PHP 8.3 and 8.4 for every pull request and push to `main`. Dependabot keeps dependencies and the workflow up to date.

## License

MIT
