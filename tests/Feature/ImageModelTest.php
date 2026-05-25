<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ImageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_accessor_returns_storage_url_for_persisted_image(): void
    {
        $image = Image::create([
            'original_name'     => 'a.jpg',
            'unique_name'       => 'a_123.jpg',
            'path'              => 'images/a_123.jpg',
            'imageable_type'    => 'App\\Models\\Stub',
            'imageable_id'      => 1,
        ]);

        $this->assertSame(Storage::disk('fake')->url('images/a_123.jpg'), $image->url);
    }

    public function test_url_accessor_returns_null_when_path_missing(): void
    {
        $image = new Image();

        $this->assertNull($image->url);
    }

    public function test_url_is_appended_to_serialized_output(): void
    {
        $image = Image::create([
            'original_name'     => 'a.jpg',
            'unique_name'       => 'a_123.jpg',
            'path'              => 'images/a_123.jpg',
            'imageable_type'    => 'App\\Models\\Stub',
            'imageable_id'      => 1,
        ]);

        $this->assertArrayHasKey('url', $image->toArray());
    }
}
