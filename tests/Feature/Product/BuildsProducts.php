<?php

namespace Tests\Feature\Product;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\SubCategory;
use Illuminate\Http\UploadedFile;

/**
 * Shared fixtures for the product tests: an active category chain, a brand and a valid form payload.
 */
trait BuildsProducts
{
    protected ?Category $category = null;

    protected ?SubCategory $sub = null;

    protected ?ChildCategory $child = null;

    protected ?Brand $brand = null;

    protected function catalog(): void
    {
        $this->category ??= Category::factory()->create(['name' => 'Electronics']);
        $this->sub ??= SubCategory::factory()->create(['category_id' => $this->category->id, 'name' => 'Phones']);
        $this->child ??= ChildCategory::factory()->create(['category_id' => $this->category->id, 'sub_category_id' => $this->sub->id, 'name' => 'Smart Phones']);
        $this->brand ??= Brand::factory()->create(['name' => 'Northland']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        $this->catalog();

        return array_merge([
            'name' => 'Galaxy Phone',
            'sku' => 'GP-100',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->sub->id,
            'child_category_id' => $this->child->id,
            'brand_id' => $this->brand->id,
            'short_description' => 'A fast phone.',
            'long_description' => '<p>Great <strong>phone</strong></p>',
            'price' => '200.00',
            'offer_price' => '',
            'offer_start_date' => '',
            'offer_end_date' => '',
            'stock_qty' => 5,
            'video_url' => '',
            'seo_title' => '',
            'seo_description' => '',
            'status' => 'active',
            'thumbnail' => UploadedFile::fake()->image('thumb.jpg', 800, 800),
            'gallery' => [UploadedFile::fake()->image('g1.jpg'), UploadedFile::fake()->image('g2.png')],
            'specifications' => [
                ['key' => 'Material', 'value' => 'Aluminium'],
                ['key' => '', 'value' => ''], // the form always sends an empty row
            ],
        ], $overrides);
    }
}
