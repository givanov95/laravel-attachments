<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Support;

use Givanov95\LaravelAttachments\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * The delete / restore / staging logic shared by `HasImages` and `HasFiles`.
 * It lives in a class rather than a trait because a model may use both.
 *
 * @internal
 */
final class AttachmentLifecycle
{
    /**
     * @param MorphMany<covariant Attachment, covariant Model> $relation
     */
    public static function deleted(Model $owner, MorphMany $relation): void
    {
        $forceDeleting = method_exists($owner, 'isForceDeleting') && $owner->isForceDeleting();
        $softDeletable = method_exists($owner, 'trashed');

        // Force delete, or an owner without SoftDeletes → remove the rows and
        // their physical files (forceDelete fires the model's `forceDeleted` hook).
        if ($forceDeleting || ! $softDeletable) {
            $relation->withTrashed()->get()->each(function (Attachment $attachment): void {
                $attachment->forceDelete();
            });

            return;
        }

        // Soft delete → soft-delete the children too, stamped with the owner's
        // exact deleted_at so restore() can match this deletion batch precisely.
        $relation->update(['deleted_at' => $owner->getAttribute('deleted_at')]);
    }

    /**
     * @param MorphMany<covariant Attachment, covariant Model> $relation
     */
    public static function restoring(Model $owner, MorphMany $relation): void
    {
        $relation
            ->onlyTrashed()
            ->where('deleted_at', $owner->getAttribute('deleted_at'))
            ->restore();
    }

    /**
     * Stamps the uploads with the owner's morph keys, section and next order.
     *
     * @param  MorphMany<covariant Attachment, covariant Model> $relation
     * @param  Collection<int, Attachment>                      $uploads
     * @return Collection<int, Attachment>
     */
    public static function stage(Model $owner, MorphMany $relation, Collection $uploads, string $section): Collection
    {
        $maxOrder = (int) $relation->where('section', $section)->max('order');

        return $uploads->values()->each(function (Attachment $attachment, int $index) use ($owner, $relation, $section, $maxOrder): void {
            $attachment->setAttribute($relation->getMorphType(), $owner->getMorphClass());
            $attachment->setAttribute($relation->getForeignKeyName(), $owner->getKey());
            $attachment->section = $section;
            $attachment->order = $maxOrder + $index + 1;
        });
    }

    /**
     * @param  Collection<int, Attachment>        $items
     * @param  array<int, string>                 $sections
     * @return array<string, Collection<int, Attachment>>
     */
    public static function group(Collection $items, array $sections): array
    {
        $grouped = [];

        foreach ($sections as $section) {
            $grouped[$section] = $items
                ->where('section', $section)
                ->sortBy('order')
                ->values();
        }

        return $grouped;
    }
}
