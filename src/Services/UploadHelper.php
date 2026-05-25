<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Services;

use Givanov95\LaravelAttachments\Models\File;
use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Services\Support\FileStr;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class UploadHelper
{
    /**
     * Store all uploaded files under `$requestKey` on disk and return them as
     * unsaved `File` models, ready to be passed to `HasFiles::setFiles()`.
     *
     * @param array<string, mixed>|null $request    Validated request payload.
     * @param string                    $requestKey Key under which UploadedFile[] live.
     * @param string                    $directory  Subdirectory under the storage disk.
     * @param string|null               $disk       Storage disk name; falls back to config.
     */
    public static function uploadMultipleFiles(
        ?array $request,
        string $requestKey,
        string $directory = '',
        ?string $disk = null
    ): Collection {
        return self::storeAll($request[$requestKey] ?? null, $directory, $disk ?? self::defaultDisk(), File::class);
    }

    /**
     * Same as `uploadMultipleFiles` but returns `Image` models.
     */
    public static function uploadMultipleImages(
        ?array $request,
        string $requestKey,
        string $directory = '',
        ?string $disk = null
    ): Collection {
        return self::storeAll($request[$requestKey] ?? null, $directory, $disk ?? self::defaultDisk(), Image::class);
    }

    /**
     * @param array<int, mixed>|null   $uploads     Raw request payload — entries are filtered at runtime.
     * @param class-string<File|Image> $modelClass
     */
    private static function storeAll(?array $uploads, string $directory, string $disk, string $modelClass): Collection
    {
        $stored = new Collection();

        if (! $uploads) {
            return $stored;
        }

        foreach ($uploads as $upload) {
            if (! $upload instanceof UploadedFile) {
                continue;
            }

            $uniqueName = FileStr::generateUniqueFileName($upload);
            $path = $upload->storeAs($directory, $uniqueName, $disk);

            $stored->push(new $modelClass([
                'original_name' => $upload->getClientOriginalName(),
                'unique_name'   => $uniqueName,
                'path'          => $path,
                'size'          => $upload->getSize(),
            ]));
        }

        return $stored;
    }

    private static function defaultDisk(): string
    {
        return (string) config('attachments.disk', 'public');
    }
}
