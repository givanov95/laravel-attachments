<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Concerns;

use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Support\AttachmentLifecycle;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

trait HasImages
{
    protected Collection $stagedImages;

    /**
     * Override on the model to cache the first image's path on a column
     * (e.g. `image_path`) so it can be SELECT-ed without joining `images`.
     * Return null (default) to disable.
     *
     * Using a method instead of a property avoids PHP's trait property
     * collision when consumers redeclare with a different default.
     */
    protected function profileImageColumn(): ?string
    {
        return null;
    }

    public static function bootHasImages(): void
    {
        static::saved(function ($model): void {
            if (! isset($model->stagedImages) || $model->stagedImages->isEmpty()) {
                return;
            }

            $model->images()->saveMany($model->stagedImages);
            $model->stagedImages = new Collection();
            $model->refreshProfileImagePath();
        });

        static::deleted(function ($model): void {
            AttachmentLifecycle::deleted($model, $model->images());
        });

        // `restoring`/`restored` are only available on models that use SoftDeletes.
        if (method_exists(static::class, 'restoring')) {
            static::restoring(function ($model): void {
                AttachmentLifecycle::restoring($model, $model->images());
            });

            static::restored(function ($model): void {
                $model->refreshProfileImagePath();
            });
        }
    }

    /**
     * @return MorphMany<Image, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('order');
    }

    /**
     * @return MorphOne<Image, $this>
     */
    public function firstImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->ofMany('order', 'min');
    }

    /**
     * @return MorphOne<Image, $this>
     */
    public function latestImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    public function setImages(?Collection $uploadedImages, string $section = 'default'): self
    {
        if (! isset($this->stagedImages)) {
            $this->stagedImages = new Collection();
        }

        if (! $uploadedImages || $uploadedImages->isEmpty()) {
            return $this;
        }

        $this->stagedImages = $this->stagedImages->merge(
            AttachmentLifecycle::stage($this, $this->images(), $uploadedImages, $section)
        );

        return $this;
    }

    /**
     * @param  array<int, string> $sections
     * @return array<string, Collection>
     */
    public function getGroupedImages(array $sections): array
    {
        return AttachmentLifecycle::group($this->images, $sections);
    }

    public function refreshProfileImagePath(): void
    {
        $column = $this->profileImageColumn();

        if (! $column) {
            return;
        }

        $newPath = $this->firstImage()->first()?->path;

        if ($this->{$column} === $newPath) {
            return;
        }

        $this->{$column} = $newPath;
        $this->saveQuietly();
    }
}
