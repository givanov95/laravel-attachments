<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Controllers;

use Givanov95\LaravelAttachments\Models\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImageController extends AttachmentController
{
    public function destroy(Image $image): RedirectResponse
    {
        return $this->softDelete($image, __('Image successfully removed'));
    }

    public function order(Request $request): RedirectResponse
    {
        return $this->reorder($request, Image::class);
    }
}
