<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Concerns\HasImages;
use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FakeProduct extends Model
{
    use HasImages;

    protected $table = 'fake_products';

    protected $fillable = ['name'];
}

class FakeSoftProduct extends Model
{
    use HasImages, SoftDeletes;

    protected $table = 'fake_soft_products';

    protected $fillable = ['name'];
}

class HasImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('fake_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('fake_soft_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeSoftProductWithImages(string ...$paths): FakeSoftProduct
    {
        $product = new FakeSoftProduct(['name' => 'p']);
        $product->save();

        $images = new Collection();

        foreach ($paths as $path) {
            Storage::disk('fake')->put($path, 'fake-bytes');
            $images->push(new Image([
                'original_name' => $path,
                'unique_name'   => $path,
                'path'          => $path,
            ]));
        }

        $product->setImages($images);
        $product->save();

        return $product;
    }

    public function test_set_images_persists_in_saved_hook(): void
    {
        $product = new FakeProduct(['name' => 'p']);
        $product->save();

        $product->setImages(new Collection([
            new Image(['original_name' => 'a.jpg', 'unique_name' => 'a_1.jpg', 'path' => 'a_1.jpg']),
            new Image(['original_name' => 'b.jpg', 'unique_name' => 'b_1.jpg', 'path' => 'b_1.jpg']),
        ]));
        $product->save();

        $this->assertDatabaseCount('images', 2);
        $this->assertCount(2, $product->fresh()->images);
    }

    public function test_set_images_with_multiple_sections_accumulates(): void
    {
        $product = new FakeProduct(['name' => 'p']);
        $product->save();

        $product
            ->setImages(new Collection([
                new Image(['original_name' => 'main.jpg', 'unique_name' => 'main_1.jpg', 'path' => 'main_1.jpg']),
            ]), 'default')
            ->setImages(new Collection([
                new Image(['original_name' => 'chart.jpg', 'unique_name' => 'chart_1.jpg', 'path' => 'chart_1.jpg']),
            ]), 'size_chart');
        $product->save();

        $grouped = $product->fresh()->getGroupedImages(['default', 'size_chart']);

        $this->assertCount(1, $grouped['default']);
        $this->assertCount(1, $grouped['size_chart']);
        $this->assertSame('main.jpg', $grouped['default']->first()->original_name);
        $this->assertSame('chart.jpg', $grouped['size_chart']->first()->original_name);
    }

    public function test_section_orders_are_independent(): void
    {
        $product = new FakeProduct(['name' => 'p']);
        $product->save();

        $product->setImages(new Collection([
            new Image(['original_name' => 'a.jpg', 'unique_name' => 'a.jpg', 'path' => 'a.jpg']),
            new Image(['original_name' => 'b.jpg', 'unique_name' => 'b.jpg', 'path' => 'b.jpg']),
        ]), 'default');
        $product->save();

        $orders = $product->fresh()->images->pluck('order')->all();
        $this->assertSame([1, 2], $orders);
    }

    public function test_deleting_product_cascades_images(): void
    {
        $product = new FakeProduct(['name' => 'p']);
        $product->save();
        $product->setImages(new Collection([
            new Image(['original_name' => 'a.jpg', 'unique_name' => 'a.jpg', 'path' => 'a.jpg']),
        ]));
        $product->save();

        $this->assertDatabaseCount('images', 1);

        $product->delete();

        $this->assertDatabaseCount('images', 0);
    }

    public function test_soft_deleting_parent_soft_deletes_images_and_keeps_disk(): void
    {
        $product = $this->makeSoftProductWithImages('a.jpg', 'b.jpg');

        $product->delete();

        // Rows are kept but trashed; physical files remain on disk.
        $this->assertSame(0, Image::count());
        $this->assertSame(2, Image::withTrashed()->count());
        Storage::disk('fake')->assertExists('a.jpg');
        Storage::disk('fake')->assertExists('b.jpg');
    }

    public function test_restoring_parent_restores_its_images(): void
    {
        $product = $this->makeSoftProductWithImages('a.jpg', 'b.jpg');

        $product->delete();
        $product->restore();

        $this->assertSame(2, Image::count());
        $this->assertCount(2, $product->fresh()->images);
    }

    public function test_force_deleting_parent_removes_images_and_disk_files(): void
    {
        $product = $this->makeSoftProductWithImages('a.jpg', 'b.jpg');

        $product->forceDelete();

        $this->assertDatabaseCount('images', 0);
        Storage::disk('fake')->assertMissing('a.jpg');
        Storage::disk('fake')->assertMissing('b.jpg');
    }

    public function test_force_deleting_parent_also_purges_previously_trashed_images(): void
    {
        $product = $this->makeSoftProductWithImages('a.jpg', 'b.jpg');

        $product->delete();      // soft: children trashed, files kept
        $product->forceDelete(); // permanent: everything (incl. trashed) purged

        $this->assertSame(0, Image::withTrashed()->count());
        Storage::disk('fake')->assertMissing('a.jpg');
        Storage::disk('fake')->assertMissing('b.jpg');
    }

    public function test_individually_deleted_image_is_not_restored_with_parent(): void
    {
        $product = $this->makeSoftProductWithImages('a.jpg', 'b.jpg');

        // 'a' is removed on its own, well before the parent is deleted.
        Carbon::setTestNow(Carbon::parse('2026-08-04 12:00:00'));
        $imageA = Image::where('original_name', 'a.jpg')->firstOrFail();
        $imageA->delete();

        // The whole product is deleted (and later restored) 10 minutes later.
        Carbon::setTestNow(Carbon::parse('2026-08-04 12:10:00'));
        $product->delete();
        $product->restore();

        $restored = $product->fresh()->images;

        $this->assertCount(1, $restored);
        $this->assertSame('b.jpg', $restored->first()->original_name);
        $this->assertSoftDeleted('images', ['id' => $imageA->id]);
    }
}
