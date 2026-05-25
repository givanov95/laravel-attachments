<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int         $id
 * @property string      $fileable_type
 * @property int         $fileable_id
 * @property string      $original_name
 * @property string      $unique_name
 * @property string      $path
 * @property int         $order
 * @property string|null $section
 * @property int|null    $size
 * @property string|null $url
 */
class File extends Model
{
    protected $fillable = [
        'fileable_type',
        'fileable_id',
        'original_name',
        'unique_name',
        'path',
        'order',
        'section',
        'size',
    ];

    protected $appends = ['url'];

    public function fileable(): MorphTo
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
