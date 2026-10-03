<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Controllers;

use Givanov95\LaravelAttachments\Authorization\AttachmentAuthorizer;
use Givanov95\LaravelAttachments\Models\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ImageController extends Controller
{
    public function destroy(Image $image): RedirectResponse
    {
        AttachmentAuthorizer::authorize([$image], AttachmentAuthorizer::UPDATE);

        // Soft delete: the row is trashed and the physical file is kept. It is
        // removed from disk only on forceDelete (parent purge or attachments:prune).
        $image->delete();

        return back()->with('success', __('Image successfully removed'));
    }

    public function order(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'orderArray'   => ['required', 'array', 'min:1'],
            'orderArray.*' => ['integer', 'exists:images,id'],
        ]);

        // Authorize every parent before touching a single row: one foreign id
        // rejects the whole request and nothing is reordered.
        AttachmentAuthorizer::authorize(
            Image::query()->with('imageable')->whereKey($validated['orderArray'])->get(),
            AttachmentAuthorizer::UPDATE,
        );

        DB::transaction(function () use ($validated): void {
            foreach ($validated['orderArray'] as $position => $id) {
                Image::whereKey($id)->update(['order' => $position + 1]);
            }
        });

        return back();
    }
}
