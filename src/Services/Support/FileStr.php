<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Services\Support;

use Illuminate\Http\UploadedFile;

class FileStr
{
    /**
     * Extensions kept as-is by default. Anything else — including content a
     * browser renders on the app's origin (html, svg, xml, js) or a server may
     * execute (php, cgi, …) — is stored as `.bin`. Apps can opt in to more
     * through the `attachments.extra_extensions` config key.
     */
    private const ALLOWED_EXTENSIONS = [
        // images
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'heic', 'heif', 'bmp', 'tif', 'tiff',
        // documents
        'pdf', 'txt', 'csv', 'rtf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
        // archives
        'zip', '7z', 'rar', 'gz', 'tar',
        // audio / video
        'mp3', 'm4a', 'wav', 'ogg', 'mp4', 'mov', 'webm',
    ];

    /**
     * Produce a collision-safe filename: `<sanitized-stem>_<time>_<uniqid>.<ext>`.
     *
     * The extension is derived from the file's detected MIME type, never from
     * the client-supplied name, so an upload cannot choose to be stored as
     * `.php`. The client's name is kept in `original_name` for display/download.
     */
    public static function generateUniqueFileName(UploadedFile $file): string
    {
        $stem = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitized = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $stem) ?? '';
        $sanitized = trim(mb_substr($sanitized, 0, 80), '_');

        if ($sanitized === '') {
            $sanitized = 'file';
        }

        return "{$sanitized}_".time().'_'.uniqid().'.'.self::safeExtension($file);
    }

    private static function safeExtension(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->guessExtension());

        if (! self::isValidExtension($extension)) {
            return 'bin';
        }

        $allowed = in_array($extension, self::ALLOWED_EXTENSIONS, true)
            || in_array($extension, self::extraExtensions(), true);

        return $allowed ? $extension : 'bin';
    }

    /**
     * Extensions the app explicitly allowed on top of the default list,
     * normalized to lowercase without a leading dot. Invalid entries are dropped.
     *
     * @return list<string>
     */
    private static function extraExtensions(): array
    {
        $configured = config('attachments.extra_extensions', []);

        if (! is_array($configured)) {
            return [];
        }

        $extensions = [];

        foreach ($configured as $value) {
            if (! is_string($value)) {
                continue;
            }

            $extension = strtolower(ltrim(trim($value), '.'));

            if (self::isValidExtension($extension)) {
                $extensions[] = $extension;
            }
        }

        return $extensions;
    }

    private static function isValidExtension(string $extension): bool
    {
        return preg_match('/^[a-z0-9]{1,10}$/', $extension) === 1;
    }
}
