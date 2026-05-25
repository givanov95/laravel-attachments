<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Concerns;

use Givanov95\LaravelAttachments\Models\Image;
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

        static::deleting(function ($model): void {
            $model->images()->delete();
        });
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('order');
    }

    public function firstImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->ofMany('order', 'min');
    }

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

        $maxOrder = (int) $this->images()->where('section', $section)->max('order');

        foreach ($uploadedImages->values() as $index => $image) {
            $image->imageable_type = $this->getMorphClass();
            $image->imageable_id = $this->id;
            $image->section = $section;
            $image->order = $maxOrder + $index + 1;

            $this->stagedImages->push($image);
        }

        return $this;
    }

    /**
     * @param  array<int, string> $sections
     * @return array<string, Collection>
     */
    public function getGroupedImages(array $sections): array
    {
        $grouped = [];

        foreach ($sections as $section) {
            $grouped[$section] = $this->images
                ->where('section', $section)
                ->sortBy('order')
                ->values();
        }

        return $grouped;
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
