<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

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
class Image extends Attachment
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

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function morphName(): string
    {
        return 'imageable';
    }
}
