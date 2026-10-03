<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Models\File;
use Givanov95\LaravelAttachments\Tests\Support\InteractsWithAttachments;
use Givanov95\LaravelAttachments\Tests\Support\PolicylessOwner;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class FileControllerTest extends TestCase
{
    use InteractsWithAttachments, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAttachmentOwners();
    }

    public function test_owner_can_download_the_file(): void
    {
        $file = $this->makeFile($this->makeOwner(1));

        $response = $this->actingAsUser(1)->get(route('files.download', $file));

        $response->assertOk();
        $this->assertSame('secret-file-bytes', $response->streamedContent());
    }

    public function test_download_of_another_users_file_is_forbidden(): void
    {
        $file = $this->makeFile($this->makeOwner(1));

        $response = $this->actingAsUser(2)->get(route('files.download', $file));

        $response->assertForbidden();
        $this->assertStringNotContainsString('secret-file-bytes', (string) $response->getContent());
    }

    public function test_download_is_forbidden_when_the_parent_has_no_policy(): void
    {
        $file = $this->makeFile($this->makeOwner(1, PolicylessOwner::class));

        $this->actingAsUser(1)->get(route('files.download', $file))->assertForbidden();
    }

    public function test_download_is_forbidden_for_an_orphaned_file(): void
    {
        $file = $this->makeFile($this->makeOwner(1));
        $file->update(['fileable_id' => 9999]);

        $this->actingAsUser(1)->get(route('files.download', $file))->assertForbidden();
    }

    public function test_guest_cannot_download(): void
    {
        $file = $this->makeFile($this->makeOwner(1));

        $this->getJson(route('files.download', $file))->assertUnauthorized();
    }

    public function test_abilities_can_be_remapped_in_config(): void
    {
        config(['attachments.abilities.view' => 'download']);
        $file = $this->makeFile($this->makeOwner(1));

        $this->actingAsUser(1)->get(route('files.download', $file))->assertOk();
        $this->actingAsUser(2)->get(route('files.download', $file))->assertForbidden();
    }

    public function test_owner_destroy_soft_deletes_file_and_keeps_disk_file(): void
    {
        $file = $this->makeFile($this->makeOwner(1));

        $this->actingAsUser(1)->delete(route('files.destroy', $file));

        $this->assertSoftDeleted('files', ['id' => $file->id]);
        Storage::disk('fake')->assertExists('files/doc.pdf');
    }

    public function test_destroy_of_another_users_file_is_forbidden(): void
    {
        $file = $this->makeFile($this->makeOwner(1));

        $this->actingAsUser(2)->delete(route('files.destroy', $file))->assertForbidden();

        $this->assertNotSoftDeleted('files', ['id' => $file->id]);
    }

    public function test_guest_cannot_destroy(): void
    {
        $file = $this->makeFile($this->makeOwner(1));

        $this->deleteJson(route('files.destroy', $file))->assertUnauthorized();

        $this->assertNotSoftDeleted('files', ['id' => $file->id]);
    }

    public function test_owner_order_rewrites_file_order_to_array_position(): void
    {
        $owner = $this->makeOwner(1);
        $a = $this->makeFile($owner, 1, 'a.pdf');
        $b = $this->makeFile($owner, 2, 'b.pdf');

        $this->actingAsUser(1)->put(route('files.order'), ['orderArray' => [$b->id, $a->id]])->assertRedirect();

        $this->assertSame(1, File::find($b->id)->order);
        $this->assertSame(2, File::find($a->id)->order);
    }

    public function test_order_with_a_foreign_id_mixed_in_changes_nothing(): void
    {
        $mine = $this->makeFile($this->makeOwner(1), 5, 'mine.pdf');
        $theirs = $this->makeFile($this->makeOwner(2), 9, 'theirs.pdf');

        $this->actingAsUser(1)
            ->put(route('files.order'), ['orderArray' => [$theirs->id, $mine->id]])
            ->assertForbidden();

        $this->assertSame(5, File::find($mine->id)->order);
        $this->assertSame(9, File::find($theirs->id)->order);
    }

    public function test_guest_cannot_reorder(): void
    {
        $file = $this->makeFile($this->makeOwner(1), 7);

        $this->putJson(route('files.order'), ['orderArray' => [$file->id]])->assertUnauthorized();

        $this->assertSame(7, File::find($file->id)->order);
    }
}
