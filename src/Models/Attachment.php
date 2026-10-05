<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int         $id
 * @property string      $original_name
 * @property string      $unique_name
 * @property string      $path
 * @property int         $order
 * @property string|null $section
 * @property int|null    $size
 * @property string|null $url
 * @property Carbon|null $deleted_at
 *
 * Behaviour shared by `Image` and `File`. Subclasses declare their own
 * `$fillable`, the `morphTo` relation to the parent and its name.
 */
abstract class Attachment extends Model
{
    use SoftDeletes;

    protected $appends = ['url'];

    protected static function booted(): void
    {
        // Physical file removal happens only on a permanent (force) delete,
        // never on a soft delete. This is the single place the disk is touched.
        static::forceDeleted(function (Attachment $attachment): void {
            if ($attachment->path) {
                Storage::disk(config('attachments.disk', 'public'))->delete($attachment->path);
            }
        });
    }

    /**
     * Name of the `morphTo` relation to the parent (`imageable` / `fileable`).
     */
    abstract public function morphName(): string;

    /**
     * The record this attachment hangs off, or null when it is gone.
     */
    public function attachedTo(): ?Model
    {
        $parent = $this->getRelationValue($this->morphName());

        return $parent instanceof Model ? $parent : null;
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
