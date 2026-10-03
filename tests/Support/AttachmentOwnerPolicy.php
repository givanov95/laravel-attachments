<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Support;

use Illuminate\Contracts\Auth\Authenticatable;

class AttachmentOwnerPolicy
{
    public function view(Authenticatable $user, AttachmentOwner $owner): bool
    {
        return $this->owns($user, $owner);
    }

    public function update(Authenticatable $user, AttachmentOwner $owner): bool
    {
        return $this->owns($user, $owner);
    }

    /** Custom ability, used to test `attachments.abilities` remapping. */
    public function download(Authenticatable $user, AttachmentOwner $owner): bool
    {
        return $this->owns($user, $owner);
    }

    private function owns(Authenticatable $user, AttachmentOwner $owner): bool
    {
        return (int) $user->getAuthIdentifier() === (int) $owner->user_id;
    }
}
