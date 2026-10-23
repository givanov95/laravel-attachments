<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Unit;

use Givanov95\LaravelAttachments\Services\Support\FileStr;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;

class FileStrTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    /**
     * An upload whose extension can only come from content sniffing: the
     * client name is an innocent `photo.jpg`.
     */
    private function sniffedUpload(string $content): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'fs');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, 'photo.jpg', null, null, true);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function browserRenderedContent(): array
    {
        return [
            'html' => ['<!DOCTYPE html><html><body><script>alert(document.cookie)</script></body></html>'],
            'svg'  => ['<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'xml'  => ['<?xml version="1.0"?><note><to>x</to></note>'],
        ];
    }

    #[DataProvider('browserRenderedContent')]
    public function test_content_a_browser_would_render_is_stored_as_bin(string $content): void
    {
        $this->assertStringEndsWith('.bin', FileStr::generateUniqueFileName($this->sniffedUpload($content)));
    }

    public function test_allowed_content_keeps_its_detected_extension(): void
    {
        $this->assertStringEndsWith('.png', FileStr::generateUniqueFileName($this->sniffedUpload((string) base64_decode(self::PNG))));
        $this->assertStringEndsWith('.pdf', FileStr::generateUniqueFileName($this->sniffedUpload("%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n")));
        // Older libmagic builds sniff CSV as text/plain; both extensions are safe.
        $this->assertMatchesRegularExpression('/\.(csv|txt)$/', FileStr::generateUniqueFileName($this->sniffedUpload("a,b,c\n1,2,3\n")));
        $this->assertStringEndsWith('.txt', FileStr::generateUniqueFileName($this->sniffedUpload("Just some notes.\nSecond line.\n")));
    }

    public function test_extra_extensions_from_config_are_kept(): void
    {
        config()->set('attachments.extra_extensions', ['.SVG', ' xml ']);

        $svg = $this->sniffedUpload(self::browserRenderedContent()['svg'][0]);
        $xml = $this->sniffedUpload(self::browserRenderedContent()['xml'][0]);
        $html = $this->sniffedUpload(self::browserRenderedContent()['html'][0]);

        $this->assertStringEndsWith('.svg', FileStr::generateUniqueFileName($svg));
        $this->assertStringEndsWith('.xml', FileStr::generateUniqueFileName($xml));
        $this->assertStringEndsWith('.bin', FileStr::generateUniqueFileName($html));
    }

    public function test_invalid_extra_extensions_are_ignored(): void
    {
        config()->set('attachments.extra_extensions', ['../html', 'h tml', '', 42, null]);

        $html = $this->sniffedUpload(self::browserRenderedContent()['html'][0]);

        $this->assertStringEndsWith('.bin', FileStr::generateUniqueFileName($html));
    }

    public function test_non_array_extra_extensions_are_ignored(): void
    {
        config()->set('attachments.extra_extensions', 'html');

        $html = $this->sniffedUpload(self::browserRenderedContent()['html'][0]);

        $this->assertStringEndsWith('.bin', FileStr::generateUniqueFileName($html));
    }

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
