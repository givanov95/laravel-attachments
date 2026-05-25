<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Feature;

use Givanov95\LaravelAttachments\Concerns\HasImages;
use Givanov95\LaravelAttachments\Models\Image;
use Givanov95\LaravelAttachments\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FakeProduct extends Model
{
    use HasImages;

    protected $table = 'fake_products';

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
}
