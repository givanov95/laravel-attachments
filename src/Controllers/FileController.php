<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Controllers;

use Givanov95\LaravelAttachments\Authorization\AttachmentAuthorizer;
use Givanov95\LaravelAttachments\Models\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends AttachmentController
{
    public function destroy(File $file): RedirectResponse
    {
        return $this->softDelete($file, __('File successfully removed'));
    }

    public function download(File $file): StreamedResponse
    {
        AttachmentAuthorizer::authorize([$file], AttachmentAuthorizer::VIEW);

        return Storage::disk(config('attachments.disk', 'public'))
            ->download($file->path, $file->original_name);
    }

    public function order(Request $request): RedirectResponse
    {
        return $this->reorder($request, File::class);
    }
}
