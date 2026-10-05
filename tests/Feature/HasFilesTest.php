<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Concerns\HasFiles;
use Givanov95\LaravelAttachments\Models\File;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FakeFileOwner extends Model
{
    use HasFiles;

    protected $table = 'fake_file_owners';

    protected $fillable = ['name'];
}

class FakeSoftFileOwner extends Model
{
    use HasFiles, SoftDeletes;

    protected $table = 'fake_soft_file_owners';

    protected $fillable = ['name'];
}

class HasFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('fake_file_owners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('fake_soft_file_owners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeSoftOwnerWithFiles(string ...$paths): FakeSoftFileOwner
    {
        $owner = new FakeSoftFileOwner(['name' => 'o']);
        $owner->save();

        $files = new Collection();

        foreach ($paths as $path) {
            Storage::disk('fake')->put($path, 'fake-bytes');
            $files->push(new File([
                'original_name' => $path,
                'unique_name'   => $path,
                'path'          => $path,
            ]));
        }

        $owner->setFiles($files);
        $owner->save();

        return $owner;
    }

    public function test_hard_deleting_owner_without_soft_deletes_removes_files_and_disk(): void
    {
        $owner = new FakeFileOwner(['name' => 'o']);
        $owner->save();
        Storage::disk('fake')->put('a.pdf', 'fake-bytes');
        $owner->setFiles(new Collection([
            new File(['original_name' => 'a.pdf', 'unique_name' => 'a.pdf', 'path' => 'a.pdf']),
        ]));
        $owner->save();

        $owner->delete();

        $this->assertDatabaseCount('files', 0);
        Storage::disk('fake')->assertMissing('a.pdf');
    }

    public function test_soft_deleting_owner_soft_deletes_files_and_keeps_disk(): void
    {
        $owner = $this->makeSoftOwnerWithFiles('a.pdf', 'b.pdf');

        $owner->delete();

        $this->assertSame(0, File::count());
        $this->assertSame(2, File::withTrashed()->count());
        Storage::disk('fake')->assertExists('a.pdf');
        Storage::disk('fake')->assertExists('b.pdf');
    }

    public function test_restoring_owner_restores_its_files(): void
    {
        $owner = $this->makeSoftOwnerWithFiles('a.pdf', 'b.pdf');

        $owner->delete();
        $owner->restore();

        $this->assertSame(2, File::count());
        $this->assertCount(2, $owner->fresh()->files);
    }

    public function test_force_deleting_owner_removes_files_and_disk(): void
    {
        $owner = $this->makeSoftOwnerWithFiles('a.pdf', 'b.pdf');

        $owner->forceDelete();

        $this->assertDatabaseCount('files', 0);
        Storage::disk('fake')->assertMissing('a.pdf');
        Storage::disk('fake')->assertMissing('b.pdf');
    }

    public function test_set_files_persists_in_saved_hook(): void
    {
        $owner = new FakeFileOwner(['name' => 'o']);
        $owner->save();

        $owner->setFiles(new Collection([
            new File(['original_name' => 'a.pdf', 'unique_name' => 'a_1.pdf', 'path' => 'a_1.pdf']),
            new File(['original_name' => 'b.pdf', 'unique_name' => 'b_1.pdf', 'path' => 'b_1.pdf']),
        ]));
        $owner->save();

        $this->assertDatabaseCount('files', 2);
        $this->assertCount(2, $owner->fresh()->files);
    }

    public function test_set_files_with_multiple_sections_accumulates(): void
    {
        $owner = new FakeFileOwner(['name' => 'o']);
        $owner->save();

        $owner
            ->setFiles(new Collection([
                new File(['original_name' => 'main.pdf', 'unique_name' => 'main_1.pdf', 'path' => 'main_1.pdf']),
            ]), 'default')
            ->setFiles(new Collection([
                new File(['original_name' => 'spec.pdf', 'unique_name' => 'spec_1.pdf', 'path' => 'spec_1.pdf']),
            ]), 'specs');
        $owner->save();

        $grouped = $owner->fresh()->getGroupedFiles(['default', 'specs']);

        $this->assertCount(1, $grouped['default']);
        $this->assertCount(1, $grouped['specs']);
        $this->assertSame('main.pdf', $grouped['default']->first()->original_name);
        $this->assertSame('spec.pdf', $grouped['specs']->first()->original_name);
    }

    public function test_section_orders_are_independent(): void
    {
        $owner = new FakeFileOwner(['name' => 'o']);
        $owner->save();

        $owner->setFiles(new Collection([
            new File(['original_name' => 'a.pdf', 'unique_name' => 'a.pdf', 'path' => 'a.pdf']),
            new File(['original_name' => 'b.pdf', 'unique_name' => 'b.pdf', 'path' => 'b.pdf']),
        ]), 'default');
        $owner->setFiles(new Collection([
            new File(['original_name' => 'c.pdf', 'unique_name' => 'c.pdf', 'path' => 'c.pdf']),
        ]), 'specs');
        $owner->save();

        $fresh = $owner->fresh();

        $this->assertSame([1, 2], $fresh->files->where('section', 'default')->pluck('order')->values()->all());
        $this->assertSame([1], $fresh->files->where('section', 'specs')->pluck('order')->values()->all());
    }

    public function test_force_deleting_owner_also_purges_previously_trashed_files(): void
    {
        $owner = $this->makeSoftOwnerWithFiles('a.pdf', 'b.pdf');

        $owner->delete();      // soft: children trashed, files kept
        $owner->forceDelete(); // permanent: everything (incl. trashed) purged

        $this->assertSame(0, File::withTrashed()->count());
        Storage::disk('fake')->assertMissing('a.pdf');
        Storage::disk('fake')->assertMissing('b.pdf');
    }

    public function test_individually_deleted_file_is_not_restored_with_owner(): void
    {
        $owner = $this->makeSoftOwnerWithFiles('a.pdf', 'b.pdf');

        // 'a' is removed on its own, well before the owner is deleted.
        Carbon::setTestNow(Carbon::parse('2026-08-04 12:00:00'));
        $fileA = File::where('original_name', 'a.pdf')->firstOrFail();
        $fileA->delete();

        // The whole owner is deleted (and later restored) 10 minutes later.
        Carbon::setTestNow(Carbon::parse('2026-08-04 12:10:00'));
        $owner->delete();
        $owner->restore();

        $restored = $owner->fresh()->files;

        $this->assertCount(1, $restored);
        $this->assertSame('b.pdf', $restored->first()->original_name);
        $this->assertSoftDeleted('files', ['id' => $fileA->id]);
    }
}
