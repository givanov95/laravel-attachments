<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Models\File;
use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PruneAttachmentsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_prune_purges_old_trashed_attachments_and_keeps_recent_ones(): void
    {
        // Trashed long ago (must be pruned).
        Storage::disk('fake')->put('old.jpg', 'x');
        Storage::disk('fake')->put('old.pdf', 'x');
        $oldImage = Image::create([
            'original_name'  => 'old.jpg',
            'unique_name'    => 'old.jpg',
            'path'           => 'old.jpg',
            'imageable_type' => 'Stub',
            'imageable_id'   => 1,
        ]);
        $oldFile = File::create([
            'original_name' => 'old.pdf',
            'unique_name'   => 'old.pdf',
            'path'          => 'old.pdf',
            'fileable_type' => 'Stub',
            'fileable_id'   => 1,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-06-01 00:00:00'));
        $oldImage->delete();
        $oldFile->delete();

        // Trashed recently (must be kept).
        Storage::disk('fake')->put('new.jpg', 'y');
        $newImage = Image::create([
            'original_name'  => 'new.jpg',
            'unique_name'    => 'new.jpg',
            'path'           => 'new.jpg',
            'imageable_type' => 'Stub',
            'imageable_id'   => 1,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-04 00:00:00'));
        $newImage->delete();

        Carbon::setTestNow(Carbon::parse('2026-08-04 12:00:00'));

        $this->artisan('attachments:prune', ['--days' => 30])->assertSuccessful();

        // Old attachments (> 30 days trashed) are permanently gone, rows and files.
        $this->assertDatabaseMissing('images', ['id' => $oldImage->id]);
        $this->assertDatabaseMissing('files', ['id' => $oldFile->id]);
        Storage::disk('fake')->assertMissing('old.jpg');
        Storage::disk('fake')->assertMissing('old.pdf');

        // Recently trashed attachment survives, still recoverable.
        $this->assertSoftDeleted('images', ['id' => $newImage->id]);
        Storage::disk('fake')->assertExists('new.jpg');
    }
}
