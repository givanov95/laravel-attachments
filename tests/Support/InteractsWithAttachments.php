<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Support;

use Givanov95\LaravelAttachments\Models\File;
use Givanov95\LaravelAttachments\Models\Image;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Fixtures for the controller authorization tests: parents owned by a user,
 * a policy guarding them, and attachments hanging off them.
 */
trait InteractsWithAttachments
{
    protected function setUpAttachmentOwners(): void
    {
        Schema::create('attachment_owners', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Gate::policy(AttachmentOwner::class, AttachmentOwnerPolicy::class);
    }

    protected function actingAsUser(int $id): static
    {
        return $this->actingAs(new GenericUser(['id' => $id]));
    }

    protected function makeOwner(int $userId, string $class = AttachmentOwner::class): Model
    {
        return $class::create(['user_id' => $userId]);
    }

    protected function makeImage(Model $owner, int $order = 0, string $name = 'a.jpg'): Image
    {
        Storage::disk('fake')->put("images/{$name}", 'image-bytes');

        return Image::create([
            'original_name'  => $name,
            'unique_name'    => $name,
            'path'           => "images/{$name}",
            'order'          => $order,
            'imageable_type' => $owner->getMorphClass(),
            'imageable_id'   => $owner->getKey(),
        ]);
    }

    protected function makeFile(Model $owner, int $order = 0, string $name = 'doc.pdf'): File
    {
        Storage::disk('fake')->put("files/{$name}", 'secret-file-bytes');

        return File::create([
            'original_name' => $name,
            'unique_name'   => $name,
            'path'          => "files/{$name}",
            'order'         => $order,
            'fileable_type' => $owner->getMorphClass(),
            'fileable_id'   => $owner->getKey(),
        ]);
    }
}
