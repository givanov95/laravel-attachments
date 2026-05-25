<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Services\Support;

use Illuminate\Http\UploadedFile;

class FileStr
{
    /**
     * Produce a collision-safe filename: `<sanitized-stem>_<time>_<uniqid>.<ext>`.
     */
    public static function generateUniqueFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $stem = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitized = preg_replace('/\s+/', '_', $stem);

        return "{$sanitized}_".time().'_'.uniqid().".{$extension}";
    }
}
