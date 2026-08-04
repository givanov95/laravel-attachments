<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Console;

use Givanov95\LaravelAttachments\Models\File;
use Givanov95\LaravelAttachments\Models\Image;
use Illuminate\Console\Command;

class PruneAttachmentsCommand extends Command
{
    protected $signature = 'attachments:prune {--days= : Permanently delete trashed attachments older than this many days}';

    protected $description = 'Permanently delete soft-deleted files/images (and their physical files) older than the retention period.';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('attachments.prune_days', 30);

        $cutoff = now()->subDays($days);

        $files = File::onlyTrashed()->where('deleted_at', '<', $cutoff)->get();
        $images = Image::onlyTrashed()->where('deleted_at', '<', $cutoff)->get();

        // forceDelete() fires each model's `forceDeleted` hook, which removes the
        // physical file from disk.
        $files->each(function (File $file): void {
            $file->forceDelete();
        });

        $images->each(function (Image $image): void {
            $image->forceDelete();
        });

        $this->info(sprintf('Pruned %d file(s) and %d image(s).', $files->count(), $images->count()));

        return self::SUCCESS;
    }
}
