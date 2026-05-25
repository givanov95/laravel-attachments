<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Controllers;

use Givanov95\LaravelAttachments\Models\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function destroy(Image $image): RedirectResponse
    {
        if ($image->path) {
            Storage::disk(config('attachments.disk', 'public'))->delete($image->path);
        }

        $image->delete();

        return back()->with('success', __('Image successfully removed'));
    }

    public function order(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'orderArray'   => ['required', 'array', 'min:1'],
            'orderArray.*' => ['integer', 'exists:images,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['orderArray'] as $position => $id) {
                Image::whereKey($id)->update(['order' => $position + 1]);
            }
        });

        return back();
    }
}
