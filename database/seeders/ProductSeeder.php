<?php

namespace Database\Seeders;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Local sample products (re-runnable), using the storefront template's sample images.
 * Needs categories (CategorySeeder) and works best with brands (BrandSeeder).
 * Run with: php artisan db:seed --class=ProductSeeder
 */
class ProductSeeder extends Seeder
{
    /**
     * name, category slug, price, offer price, image, hover image, flags
     *
     * @var array<int, array{0: string, 1: string, 2: float, 3: float|null, 4: string, 5: string, 6: array<int, string>}>
     */
    private const PRODUCTS = [
        ['HP 24" FHD Monitor', 'electronics', 200, 159, 'pro3.jpg', 'pro3_3.jpg', ['is_featured', 'is_top']],
        ['Men\'s Casual Watch', 'fashion', 120, 99, 'pro4.jpg', 'pro4_4.jpg', ['is_featured', 'is_best']],
        ['Leather Shoulder Bag', 'fashion', 130, null, 'pro9.jpg', 'pro9_9.jpg', ['is_top', 'is_best']],
        ['Portable Speaker', 'electronics', 220, 200, 'pro0011.jpg', 'pro0011.jpg', ['is_featured']],
        ['8.5 VA Fast Charger', 'electronics', 25, null, 'charger_2.jpg', 'charger_1.jpg', ['is_top']],
        ['Smart Wrist Watch', 'electronics', 60, 50, 'pro0010.jpg', 'pro0010.jpg', ['is_featured', 'is_best']],
        ['Women\'s Summer Dress', 'fashion', 80, null, 'pro8_8.jpg', 'pro8_8.jpg', ['is_top']],
        ['Kid\'s Party Dress', 'fashion', 45, 39, 'pro2.jpg', 'pro2.jpg', []],
    ];

    public function run(): void
    {
        $admin = Admin::first();
        $brands = Brand::active()->pluck('id')->all();

        foreach (self::PRODUCTS as $index => [$name, $categorySlug, $price, $offer, $image, $hover, $flags]) {
            $category = Category::where('slug', $categorySlug)->first() ?? Category::first();

            if (! $category) {
                $this->command?->warn('No categories: run CategorySeeder first.');

                return;
            }

            $thumbnail = $this->copy($image, 'products/thumbnails/sample-'.($index + 1).'.jpg');

            $product = Product::withTrashed()->firstOrNew(['sku' => 'SAMPLE-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)]);
            $product->fill([
                'name' => $name,
                'category_id' => $category->id,
                'brand_id' => $brands ? $brands[$index % count($brands)] : null,
                'short_description' => 'A sample product to try the store with.',
                'long_description' => '<p>This is sample content for <strong>'.e($name).'</strong>.</p>',
                'price' => $price,
                'offer_price' => $offer,
                'offer_start_date' => $offer ? today()->subDay() : null,
                'offer_end_date' => $offer ? today()->addDays(3 + $index) : null,
                'stock_qty' => $index === 7 ? 0 : 10 + $index,
                'status' => ProductStatus::Active,
            ]);
            $product->forceFill([
                'thumbnail' => $thumbnail,
                'approval_status' => ProductApprovalStatus::Approved,
                'is_featured' => in_array('is_featured', $flags, true),
                'is_top' => in_array('is_top', $flags, true),
                'is_best' => in_array('is_best', $flags, true),
                'deleted_at' => null,
            ]);
            if ($admin && ! $product->exists) {
                $product->createdBy()->associate($admin);
            }
            $product->save();

            if ($product->images()->doesntExist()) {
                $product->images()->create(['image' => $this->copy($hover, 'products/gallery/sample-'.($index + 1).'.jpg'), 'sort_order' => 0]);
            }

            if ($product->specifications()->doesntExist()) {
                $product->specifications()->createMany([
                    ['key' => 'Warranty', 'value' => '1 year', 'sort_order' => 0],
                    ['key' => 'Packaging', 'value' => 'Box', 'sort_order' => 1],
                ]);
            }
        }
    }

    private function copy(string $image, string $target): string
    {
        $source = public_path('frontend-assets/images/'.$image);

        if (is_file($source)) {
            Storage::disk('public')->put($target, file_get_contents($source));
        }

        return $target;
    }
}
