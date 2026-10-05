<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $deleted_at
 */
class File extends Attachment
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

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function morphName(): string
    {
        return 'fileable';
    }
}
