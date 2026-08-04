<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Concerns;

use Givanov95\LaravelAttachments\Models\File;
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
            $forceDeleting = method_exists($model, 'isForceDeleting') && $model->isForceDeleting();
            $softDeletable = method_exists($model, 'trashed');

            // Force delete, or a parent without SoftDeletes → remove the rows and
            // their physical files (forceDelete fires the File `forceDeleted` hook).
            if ($forceDeleting || ! $softDeletable) {
                $model->files()->withTrashed()->get()->each(function (File $file): void {
                    $file->forceDelete();
                });

                return;
            }

            // Soft delete → soft-delete the children too, stamped with the parent's
            // exact deleted_at so restore() can match this deletion batch precisely.
            $model->files()->update(['deleted_at' => $model->deleted_at]);
        });

        // `restoring` is only available on models that use SoftDeletes.
        if (method_exists(static::class, 'restoring')) {
            static::restoring(function ($model): void {
                $model->files()
                    ->onlyTrashed()
                    ->where('deleted_at', $model->deleted_at)
                    ->restore();
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

        $maxOrder = (int) $this->files()->where('section', $section)->max('order');

        foreach ($uploadedFiles->values() as $index => $file) {
            $file->fileable_type = $this->getMorphClass();
            $file->fileable_id = $this->id;
            $file->section = $section;
            $file->order = $maxOrder + $index + 1;

            $this->stagedFiles->push($file);
        }

        return $this;
    }

    /**
     * @param  array<int, string> $sections
     * @return array<string, Collection>
     */
    public function getGroupedFiles(array $sections): array
    {
        $grouped = [];

        foreach ($sections as $section) {
            $grouped[$section] = $this->files
                ->where('section', $section)
                ->sortBy('order')
                ->values();
        }

        return $grouped;
    }
}
