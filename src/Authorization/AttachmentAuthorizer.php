<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Authorization;

use Givanov95\LaravelAttachments\Models\Attachment;
use Illuminate\Support\Facades\Gate;

/**
 * Authorizes attachment routes against the record the attachment hangs off
 * (`fileable` / `imageable`), by deferring to that model's policy.
 *
 * Fails closed: an attachment whose parent is gone, or whose parent has no
 * policy, is denied (403) rather than allowed.
 */
class AttachmentAuthorizer
{
    public const VIEW = 'view';

    public const UPDATE = 'update';

    /**
     * @param iterable<Attachment> $attachments
     * @param self::VIEW|self::UPDATE $action Mapped to a policy ability via `attachments.abilities`.
     */
    public static function authorize(iterable $attachments, string $action): void
    {
        $ability = (string) config("attachments.abilities.{$action}", $action);

        $parents = [];

        foreach ($attachments as $attachment) {
            $parent = $attachment->attachedTo();

            // An orphaned attachment has no owner to inherit permissions from.
            abort_if($parent === null, 403);

            $parents[$parent::class.':'.$parent->getKey()] = $parent;
        }

        foreach ($parents as $parent) {
            Gate::authorize($ability, $parent);
        }
    }
}
