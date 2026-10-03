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

    public function test_client_supplied_php_extension_is_never_kept(): void
    {
        $file = UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]);');

        $name = FileStr::generateUniqueFileName($file);

        $this->assertStringEndsNotWith('.php', $name);
        $this->assertStringEndsWith('.bin', $name);
    }

    public function test_extension_comes_from_detected_content_not_the_client_name(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fs');
        file_put_contents($path, '<?php echo 1;');

        // A script uploaded under an innocent name is still detected as PHP.
        $script = new UploadedFile($path, 'photo.jpg', null, null, true);
        $this->assertStringEndsWith('.bin', FileStr::generateUniqueFileName($script));

        // A real image keeps its image extension even when named oddly.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        file_put_contents($path, $png);
        $image = new UploadedFile($path, 'evil.php', null, null, true);
        $this->assertStringEndsWith('.png', FileStr::generateUniqueFileName($image));

        unlink($path);
    }

    public function test_stem_is_sanitized_but_keeps_unicode_letters(): void
    {
        $file = UploadedFile::fake()->create('../Договор №1 (final).pdf', 10);

        $name = FileStr::generateUniqueFileName($file);

        $this->assertStringStartsWith('Договор_1_final_', $name);
        $this->assertStringNotContainsString('/', $name);
        $this->assertStringNotContainsString('..', $name);
    }

    public function test_empty_stem_falls_back_to_file(): void
    {
        $file = UploadedFile::fake()->create('....pdf', 10);

        $this->assertStringStartsWith('file_', FileStr::generateUniqueFileName($file));
    }
}
