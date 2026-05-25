<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Models\File;
use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Services\UploadHelper;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadHelperTest extends TestCase
{
    public function test_upload_multiple_images_stores_files_and_returns_unsaved_image_models(): void
    {
        $request = [
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.png'),
            ],
        ];

        $collection = UploadHelper::uploadMultipleImages($request, 'images', 'products');

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(Image::class, $collection->first());
        $this->assertFalse($collection->first()->exists);
        $this->assertSame('a.jpg', $collection->first()->original_name);

        Storage::disk('fake')->assertExists($collection->first()->path);
    }

    public function test_upload_multiple_files_returns_file_models(): void
    {
        $request = [
            'specs' => [UploadedFile::fake()->create('manual.pdf', 100)],
        ];

        $collection = UploadHelper::uploadMultipleFiles($request, 'specs', 'specs');

        $this->assertCount(1, $collection);
        $this->assertInstanceOf(File::class, $collection->first());
        $this->assertSame('manual.pdf', $collection->first()->original_name);
    }

    public function test_returns_empty_collection_when_key_missing(): void
    {
        $collection = UploadHelper::uploadMultipleImages(['other' => []], 'images');

        $this->assertTrue($collection->isEmpty());
    }

    public function test_skips_non_uploaded_file_entries(): void
    {
        $request = [
            'images' => [
                UploadedFile::fake()->image('ok.jpg'),
                'not-a-file',
                42,
            ],
        ];

        $collection = UploadHelper::uploadMultipleImages($request, 'images');

        $this->assertCount(1, $collection);
    }
}
