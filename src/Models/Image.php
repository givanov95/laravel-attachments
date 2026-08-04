<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int         $id
 * @property string      $imageable_type
 * @property int         $imageable_id
 * @property string      $original_name
 * @property string      $unique_name
 * @property string      $path
 * @property int         $order
 * @property string|null $section
 * @property int|null    $size
 * @property string|null $url
 * @property Carbon|null $deleted_at
 */
class Image extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'original_name',
        'unique_name',
        'path',
        'order',
        'section',
        'size',
    ];

    protected $appends = ['url'];

    protected static function booted(): void
    {
        // Physical file removal happens only on a permanent (force) delete,
        // never on a soft delete. This is the single place the disk is touched.
        static::forceDeleted(function (Image $image): void {
            if ($image->path) {
                Storage::disk(config('attachments.disk', 'public'))->delete($image->path);
            }
        });
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function url(): Attribute
    {
        return Attribute::get(
            fn () => $this->path
                ? Storage::disk(config('attachments.disk', 'public'))->url($this->path)
                : null
        );
    }
}
