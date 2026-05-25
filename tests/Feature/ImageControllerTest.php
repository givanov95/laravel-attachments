<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ImageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        // Drop the 'auth' middleware so the test doesn't need an authenticated user.
        $app['config']->set('attachments.middleware', ['web']);
    }

    public function test_destroy_removes_image_record_and_disk_file(): void
    {
        Storage::disk('fake')->put('images/a.jpg', 'fake-bytes');

        $image = Image::create([
            'original_name'  => 'a.jpg',
            'unique_name'    => 'a.jpg',
            'path'           => 'images/a.jpg',
            'imageable_type' => 'Stub',
            'imageable_id'   => 1,
        ]);

        $this->delete(route('images.destroy', $image));

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Storage::disk('fake')->assertMissing('images/a.jpg');
    }

    public function test_order_rewrites_image_order_to_array_position(): void
    {
        $imgs = collect([3, 2, 1])->map(fn ($order) => Image::create([
            'original_name'  => "i{$order}.jpg",
            'unique_name'    => "i{$order}.jpg",
            'path'           => "p{$order}.jpg",
            'order'          => $order,
            'imageable_type' => 'Stub',
            'imageable_id'   => 1,
        ]));

        // Reorder: oldest first.
        $newOrder = [$imgs[2]->id, $imgs[1]->id, $imgs[0]->id];

        $this->put(route('images.order'), ['orderArray' => $newOrder]);

        $this->assertSame(1, Image::find($imgs[2]->id)->order);
        $this->assertSame(2, Image::find($imgs[1]->id)->order);
        $this->assertSame(3, Image::find($imgs[0]->id)->order);
    }
}
