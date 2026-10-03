<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Controllers;

use Givanov95\LaravelAttachments\Authorization\AttachmentAuthorizer;
use Givanov95\LaravelAttachments\Models\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function destroy(File $file): RedirectResponse
    {
        AttachmentAuthorizer::authorize([$file], AttachmentAuthorizer::UPDATE);

        // Soft delete: the row is trashed and the physical file is kept. It is
        // removed from disk only on forceDelete (parent purge or attachments:prune).
        $file->delete();

        return back()->with('success', __('File successfully removed'));
    }

    public function download(File $file): StreamedResponse
    {
        AttachmentAuthorizer::authorize([$file], AttachmentAuthorizer::VIEW);

        return Storage::disk(config('attachments.disk', 'public'))
            ->download($file->path, $file->original_name);
    }

    public function order(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'orderArray'   => ['required', 'array', 'min:1'],
            'orderArray.*' => ['integer', 'exists:files,id'],
        ]);

        // Authorize every parent before touching a single row: one foreign id
        // rejects the whole request and nothing is reordered.
        AttachmentAuthorizer::authorize(
            File::query()->with('fileable')->whereKey($validated['orderArray'])->get(),
            AttachmentAuthorizer::UPDATE,
        );

        DB::transaction(function () use ($validated): void {
            foreach ($validated['orderArray'] as $position => $id) {
                File::whereKey($id)->update(['order' => $position + 1]);
            }
        });

        return back();
    }
}
