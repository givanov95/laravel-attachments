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

        static::deleting(function ($model): void {
            $model->files()->delete();
        });
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderBy('order');
    }

    public function firstFile(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable')->ofMany('order', 'min');
    }

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
