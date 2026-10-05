<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Controllers;

use Givanov95\LaravelAttachments\Authorization\AttachmentAuthorizer;
use Givanov95\LaravelAttachments\Models\Attachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

abstract class AttachmentController extends Controller
{
    protected function softDelete(Attachment $attachment, string $message): RedirectResponse
    {
        AttachmentAuthorizer::authorize([$attachment], AttachmentAuthorizer::UPDATE);

        // Soft delete: the row is trashed and the physical file is kept. It is
        // removed from disk only on forceDelete (parent purge or attachments:prune).
        $attachment->delete();

        return back()->with('success', $message);
    }

    /**
     * @param class-string<Attachment> $model
     */
    protected function reorder(Request $request, string $model): RedirectResponse
    {
        $instance = new $model();

        $validated = $request->validate([
            'orderArray'   => ['required', 'array', 'min:1'],
            'orderArray.*' => ['integer', 'exists:'.$instance->getTable().','.$instance->getKeyName()],
        ]);

        // Authorize every parent before touching a single row: one foreign id
        // rejects the whole request and nothing is reordered.
        AttachmentAuthorizer::authorize(
            $model::query()->with($instance->morphName())->whereKey($validated['orderArray'])->get(),
            AttachmentAuthorizer::UPDATE,
        );

        DB::transaction(function () use ($model, $validated): void {
            foreach ($validated['orderArray'] as $position => $id) {
                $model::whereKey($id)->update(['order' => $position + 1]);
            }
        });

        return back();
    }
}
