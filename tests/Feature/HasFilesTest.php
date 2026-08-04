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
}
