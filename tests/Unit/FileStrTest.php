<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Unit;

use Givanov95\LaravelAttachments\Services\Support\FileStr;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class FileStrTest extends TestCase
{
    public function test_generates_unique_name_preserving_extension(): void
    {
        $file = UploadedFile::fake()->create('My Document.pdf', 100);

        $name = FileStr::generateUniqueFileName($file);

        $this->assertStringEndsWith('.pdf', $name);
        $this->assertStringStartsWith('My_Document_', $name);
        $this->assertStringNotContainsString(' ', $name);
    }

    public function test_two_uploads_of_same_filename_produce_different_unique_names(): void
    {
        $a = UploadedFile::fake()->create('photo.jpg', 100);
        $b = UploadedFile::fake()->create('photo.jpg', 100);

        $this->assertNotSame(
            FileStr::generateUniqueFileName($a),
            FileStr::generateUniqueFileName($b)
        );
    }
}
