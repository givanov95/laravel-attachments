<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Concerns;

use Givanov95\LaravelAttachments\Models\File;
use Givanov95\LaravelAttachments\Support\AttachmentLifecycle;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

trait HasFiles
{
    protected Collection $stagedFiles;

    public static function bootHasFiles(): void
    {
        static::saved(function ($model): void {
            if (! isset($model->stagedFiles) || $model->stagedFiles->isEmpty()) {
                return;
            }

            $model->files()->saveMany($model->stagedFiles);
            $model->stagedFiles = new Collection();
        });

        static::deleted(function ($model): void {
            AttachmentLifecycle::deleted($model, $model->files());
        });

        // `restoring` is only available on models that use SoftDeletes.
        if (method_exists(static::class, 'restoring')) {
            static::restoring(function ($model): void {
                AttachmentLifecycle::restoring($model, $model->files());
            });
        }
    }

    /**
     * @return MorphMany<File, $this>
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderBy('order');
    }

    /**
     * @return MorphOne<File, $this>
     */
    public function firstFile(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable')->ofMany('order', 'min');
    }

    /**
     * @return MorphOne<File, $this>
     */
    public function latestFile(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable')->latestOfMany();
    }

    public function setFiles(?Collection $uploadedFiles, string $section = 'default'): self
    {
        if (! isset($this->stagedFiles)) {
            $this->stagedFiles = new Collection();
        }

        if (! $uploadedFiles || $uploadedFiles->isEmpty()) {
            return $this;
        }

        $this->stagedFiles = $this->stagedFiles->merge(
            AttachmentLifecycle::stage($this, $this->files(), $uploadedFiles, $section)
        );

        return $this;
    }

    /**
     * @param  array<int, string> $sections
     * @return array<string, Collection>
     */
    public function getGroupedFiles(array $sections): array
    {
        return AttachmentLifecycle::group($this->files, $sections);
    }
}
