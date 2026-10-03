<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Services\Support;

use Illuminate\Http\UploadedFile;

class FileStr
{
    /**
     * Extensions a web server may execute or interpret. Never stored as-is,
     * whatever the client sent or the content sniffing detected.
     */
    private const UNSAFE_EXTENSIONS = [
        'php', 'phtml', 'phar', 'pht', 'phps', 'pgif', 'inc',
        'shtml', 'shtm', 'stm', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash',
        'asp', 'aspx', 'ashx', 'cer', 'jsp', 'jspx', 'war',
        'exe', 'dll', 'bat', 'cmd', 'com', 'msi', 'htaccess', 'htpasswd',
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

        if ($extension === '' || ! preg_match('/^[a-z0-9]{1,10}$/', $extension)) {
            return 'bin';
        }

        return in_array($extension, self::UNSAFE_EXTENSIONS, true) ? 'bin' : $extension;
    }
}
