<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
 */
class Image extends Model
{
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
