<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Repositories\ProductRepository;
use App\Services\VendorStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_only_visible_products_reach_the_storefront(): void
    {
        $category = Category::factory()->create();
        $approvedVendor = Vendor::factory()->approved()->create();
        $suspendedVendor = Vendor::factory()->suspended()->create();
        $inactiveSub = SubCategory::factory()->inactive()->create(['category_id' => $category->id]);

        $visible = [
            Product::factory()->create(['name' => 'In House Visible', 'category_id' => $category->id]),
            Product::factory()->forVendor($approvedVendor)->create(['name' => 'Vendor Visible', 'category_id' => $category->id]),
        ];
        $hidden = [
            Product::factory()->pending()->create(['name' => 'Pending Hidden', 'category_id' => $category->id]),
            Product::factory()->rejected()->create(['name' => 'Rejected Hidden', 'category_id' => $category->id]),
            Product::factory()->inactive()->create(['name' => 'Inactive Hidden', 'category_id' => $category->id]),
            Product::factory()->draft()->create(['name' => 'Draft Hidden', 'category_id' => $category->id]),
            Product::factory()->forVendor($suspendedVendor)->create(['name' => 'Suspended Vendor Hidden', 'category_id' => $category->id]),
            Product::factory()->create(['name' => 'Inactive Category Hidden', 'category_id' => Category::factory()->inactive()->create()->id]),
            Product::factory()->create(['name' => 'Inactive Sub Hidden', 'category_id' => $category->id, 'sub_category_id' => $inactiveSub->id]),
        ];
        Product::factory()->create(['name' => 'Deleted Hidden', 'category_id' => $category->id])->delete();

        $this->assertEqualsCanonicalizing(
            ['In House Visible', 'Vendor Visible'],
            Product::visibleOnStorefront()->pluck('name')->all(),
        );

        $shop = $this->get(route('shop.index'))->assertOk();
        foreach ($visible as $product) {
            $shop->assertSee($product->name);
            $this->get(route('product.show', $product->slug))->assertOk()->assertSee($product->name);
        }
        foreach ($hidden as $product) {
            $shop->assertDontSee($product->name);
            $this->get(route('product.show', $product->slug))->assertNotFound();
        }
    }

    public function test_price_accessors(): void
    {
        $offer = Product::factory()->onOffer(80)->make(['price' => 100, 'stock_qty' => 0]);
        $expired = Product::factory()->make(['price' => 100, 'offer_price' => 50, 'offer_start_date' => today()->subDays(10), 'offer_end_date' => today()->subDay()]);
        $undated = Product::factory()->make(['price' => 100, 'offer_price' => 75]);

        $this->assertSame('80.00', $offer->current_price);
        $this->assertSame(20, $offer->discount_percent);
        $this->assertFalse($offer->in_stock);
        $this->assertSame('₹80.00', $offer->current_price_label);
        $this->assertNotNull($offer->offer_ends_at);

        $this->assertSame('100.00', $expired->current_price);
        $this->assertSame(0, $expired->discount_percent);
        $this->assertNull($expired->offer_ends_at);

        $this->assertSame('75.00', $undated->current_price);
        $this->assertSame(25, $undated->discount_percent);
        $this->assertNull($undated->offer_ends_at);
    }

    public function test_product_page_shows_the_details_and_sanitized_description(): void
    {
        $vendor = Vendor::factory()->approved()->create(['shop_name' => 'Gadget Hub']);
        $product = Product::factory()->forVendor($vendor)->onOffer(80)->create([
            'name' => 'Smart Watch',
            'sku' => 'SW-42',
            'long_description' => '<p>Water <strong>resistant</strong></p>',
        ]);
        $product->specifications()->createMany([
            ['key' => 'Strap', 'value' => 'Silicone', 'sort_order' => 0],
            ['key' => 'Battery', 'value' => '7 days', 'sort_order' => 1],
        ]);
        $product->images()->create(['image' => 'products/gallery/a.jpg', 'sort_order' => 0]);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('Smart Watch')
            ->assertSee('SW-42')
            ->assertSee('₹80.00')
            ->assertSee('<del>₹100.00</del>', false)
            ->assertSee('<strong>resistant</strong>', false)
            ->assertSee('Silicone')
            ->assertSee('7 days')
            ->assertSee('Gadget Hub')
            ->assertSee(route('store.show', $vendor->slug), false)
            ->assertSee('data-countdown-end', false)
            ->assertSee(route('category.show', $product->category->slug), false);
    }

    public function test_home_sections_use_flags_and_offers(): void
    {
        $electronics = Category::factory()->create(['name' => 'Electronics']);
        config(['marketplace.home_category_sections' => [$electronics->slug]]);

        Product::factory()->onOffer(80)->create(['name' => 'Flash Deal Item']);
        Product::factory()->create(['name' => 'Expired Offer Item', 'offer_price' => 50, 'offer_start_date' => today()->subDays(5), 'offer_end_date' => today()->subDay()]);
        Product::factory()->flagged('is_top')->create(['name' => 'Top Item']);
        Product::factory()->flagged('is_featured')->onOffer(90)->create(['name' => 'Featured Offer Item']);
        Product::factory()->flagged('is_best')->create(['name' => 'Best Item']);
        Product::factory()->create(['name' => 'Electronic Item', 'category_id' => $electronics->id]);
        Product::factory()->flagged('is_top')->pending()->create(['name' => 'Pending Top Item']);

        $sections = app(ProductRepository::class)->homeSections();

        $this->assertEqualsCanonicalizing(['Flash Deal Item', 'Featured Offer Item'], $sections['flash']->pluck('name')->all());
        $this->assertSame(['Top Item'], $sections['top']->pluck('name')->all());
        $this->assertSame(['Featured Offer Item'], $sections['featured']->pluck('name')->all());
        $this->assertSame(['Featured Offer Item'], $sections['hot_offers']->pluck('name')->all());
        $this->assertSame(['Best Item'], $sections['best']->pluck('name')->all());
        $this->assertSame(['Electronic Item'], $sections['category_sections'][0]['products']->pluck('name')->all());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Flash Deal Item')
            ->assertSee('Top Item')
            ->assertSee('Best Item')
            ->assertSee('Electronic Item')
            ->assertSee('Electronics')
            // The expired offer is still a normal product (newest strip), just not in Flash Sell (checked above).
            ->assertDontSee('Pending Top Item');
    }

    public function test_empty_product_sections_are_hidden(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('id="wsus__flash_sell"', false)
            ->assertDontSee('id="wsus__hot_deals"', false)
            ->assertDontSee('weekly best Sale Products');
    }

    public function test_home_cache_is_cleared_by_product_vendor_and_category_changes(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $product = Product::factory()->forVendor($vendor)->flagged('is_best')->create(['name' => 'Cached Item']);

        $warm = function () {
            $this->get(route('home'))->assertOk();
            $this->assertTrue(Cache::has(ProductRepository::HOME_CACHE_KEY));
        };

        $warm();
        $product->update(['name' => 'Renamed Item']);
        $this->assertFalse(Cache::has(ProductRepository::HOME_CACHE_KEY));
        $this->get(route('home'))->assertSee('Renamed Item');

        // Suspending the vendor hides their products straight away.
        app(VendorStatusService::class)->suspend($vendor, 'Policy breach.');
        $this->assertFalse(Cache::has(ProductRepository::HOME_CACHE_KEY));
        $this->get(route('home'))->assertDontSee('Renamed Item');

        $warm();
        $product->category->update(['is_active' => false]);
        $this->assertFalse(Cache::has(ProductRepository::HOME_CACHE_KEY));
    }

    public function test_store_page_lists_vendor_products_and_hides_non_approved_shops(): void
    {
        $vendor = Vendor::factory()->approved()->create(['shop_name' => 'Open Shop']);
        Product::factory()->forVendor($vendor)->create(['name' => 'Shop Product']);
        Product::factory()->create(['name' => 'Other Product']);

        $this->get(route('store.show', $vendor->slug))->assertOk()->assertSee('Open Shop')->assertSee('Shop Product')->assertDontSee('Other Product');

        $pending = Vendor::factory()->create();
        $this->get(route('store.show', $pending->slug))->assertNotFound();
    }

    public function test_category_pages_list_their_products_and_shop_paginates(): void
    {
        $category = Category::factory()->create(['name' => 'Fashion']);
        Product::factory()->count(13)->create(['category_id' => $category->id]);
        Product::factory()->create(['name' => 'Elsewhere Item']);

        $this->get(route('category.show', $category->slug))->assertOk()->assertDontSee('Elsewhere Item')->assertSee('page_active', false);

        $page2 = $this->get(route('shop.index', ['page' => 2]))->assertOk();
        $this->assertSame(2, substr_count($page2->getContent(), 'class="wsus__product_item'));
    }

    public function test_product_cards_use_the_model_values(): void
    {
        Product::factory()->onOffer(80)->create(['name' => 'Card Item', 'stock_qty' => 0, 'created_at' => now()]);

        $this->get(route('shop.index'))
            ->assertSee('-20%')
            ->assertSee('New')
            ->assertSee('out of stock')
            ->assertSee('frontend-assets/images/product-placeholder.svg', false);
    }
}
