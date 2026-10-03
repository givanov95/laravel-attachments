<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Tests\Support\InteractsWithAttachments;
use Givanov95\LaravelAttachments\Tests\Support\PolicylessOwner;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ImageControllerTest extends TestCase
{
    use InteractsWithAttachments, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAttachmentOwners();
    }

    public function test_owner_destroy_soft_deletes_image_and_keeps_disk_file(): void
    {
        $image = $this->makeImage($this->makeOwner(1));

        $this->actingAsUser(1)->delete(route('images.destroy', $image));

        // Row is trashed (recoverable), physical file is kept until forceDelete.
        $this->assertSoftDeleted('images', ['id' => $image->id]);
        Storage::disk('fake')->assertExists('images/a.jpg');
    }

    public function test_destroy_of_another_users_image_is_forbidden(): void
    {
        $image = $this->makeImage($this->makeOwner(1));

        $this->actingAsUser(2)->delete(route('images.destroy', $image))->assertForbidden();

        $this->assertNotSoftDeleted('images', ['id' => $image->id]);
    }

    public function test_destroy_is_forbidden_when_the_parent_has_no_policy(): void
    {
        $image = $this->makeImage($this->makeOwner(1, PolicylessOwner::class));

        $this->actingAsUser(1)->delete(route('images.destroy', $image))->assertForbidden();

        $this->assertNotSoftDeleted('images', ['id' => $image->id]);
    }

    public function test_destroy_is_forbidden_for_an_orphaned_image(): void
    {
        $owner = $this->makeOwner(1);
        $image = $this->makeImage($owner);
        $image->update(['imageable_id' => 9999]);

        $this->actingAsUser(1)->delete(route('images.destroy', $image))->assertForbidden();

        $this->assertNotSoftDeleted('images', ['id' => $image->id]);
    }

    public function test_guest_cannot_destroy(): void
    {
        $image = $this->makeImage($this->makeOwner(1));

        $this->deleteJson(route('images.destroy', $image))->assertUnauthorized();

        $this->assertNotSoftDeleted('images', ['id' => $image->id]);
    }

    public function test_force_delete_removes_image_record_and_disk_file(): void
    {
        $image = $this->makeImage($this->makeOwner(1));

        $image->forceDelete();

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Storage::disk('fake')->assertMissing('images/a.jpg');
    }

    public function test_owner_order_rewrites_image_order_to_array_position(): void
    {
        $owner = $this->makeOwner(1);
        $imgs = collect([3, 2, 1])->map(fn ($order) => $this->makeImage($owner, $order, "i{$order}.jpg"));

        // Reorder: oldest first.
        $newOrder = [$imgs[2]->id, $imgs[1]->id, $imgs[0]->id];

        $this->actingAsUser(1)->put(route('images.order'), ['orderArray' => $newOrder])->assertRedirect();

        $this->assertSame(1, Image::find($imgs[2]->id)->order);
        $this->assertSame(2, Image::find($imgs[1]->id)->order);
        $this->assertSame(3, Image::find($imgs[0]->id)->order);
    }

    public function test_order_is_forbidden_for_another_users_images(): void
    {
        $image = $this->makeImage($this->makeOwner(1), 7);

        $this->actingAsUser(2)->put(route('images.order'), ['orderArray' => [$image->id]])->assertForbidden();

        $this->assertSame(7, Image::find($image->id)->order);
    }

    public function test_order_with_a_foreign_id_mixed_in_changes_nothing(): void
    {
        $mine = $this->makeImage($this->makeOwner(1), 5, 'mine.jpg');
        $theirs = $this->makeImage($this->makeOwner(2), 9, 'theirs.jpg');

        $this->actingAsUser(1)
            ->put(route('images.order'), ['orderArray' => [$theirs->id, $mine->id]])
            ->assertForbidden();

        $this->assertSame(5, Image::find($mine->id)->order);
        $this->assertSame(9, Image::find($theirs->id)->order);
    }

    public function test_guest_cannot_reorder(): void
    {
        $image = $this->makeImage($this->makeOwner(1), 7);

        $this->putJson(route('images.order'), ['orderArray' => [$image->id]])->assertUnauthorized();

        $this->assertSame(7, Image::find($image->id)->order);
    }
}
