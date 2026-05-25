# givanov95/laravel-attachments

Polymorphic file & image attachments for Laravel.

## What's included

- `Image` + `File` Eloquent models with appended `url` accessor (uses `Storage::disk()->url()`)
- `HasImages` + `HasFiles` traits:
    - `setImages($collection, 'section')` / `setFiles($collection, 'section')` — staged + persisted in `saved()` hook
    - Multi-section support, accumulates across calls
    - Optional `$profileImageColumn` cache (uses `saveQuietly()` to avoid recursion)
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
];
```

Override `middleware` to add `'verified'`, role guards, custom guards, etc. The image/file routes are registered under this middleware stack.

## Usage

### On a model

```php
use Givanov95\LaravelAttachments\Concerns\HasImages;
use Givanov95\LaravelAttachments\Concerns\HasFiles;

class Product extends Model
{
    use HasImages, HasFiles;

    // optional — cache first image path on this column for fast listing queries
    protected ?string $profileImageColumn = 'image_path';
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

## License

MIT
