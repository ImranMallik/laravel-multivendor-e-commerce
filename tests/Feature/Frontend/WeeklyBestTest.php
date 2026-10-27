<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use App\Services\HomeProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WeeklyBestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_lists_use_the_placeholder_flags_and_only_visible_products(): void
    {
        $featured = Product::factory()->flagged('is_featured')->create(['name' => 'Featured Lamp']);
        $top = Product::factory()->flagged('is_top')->create(['name' => 'Top Lamp']);
        $best = Product::factory()->flagged('is_best')->create(['name' => 'Best Lamp']);
        Product::factory()->create(['name' => 'Plain Lamp']);
        Product::factory()->flagged('is_best')->pending()->create(['name' => 'Pending Best Lamp']);
        Product::factory()->flagged('is_featured')->inactive()->create(['name' => 'Inactive Featured Lamp']);

        $service = app(HomeProductService::class);

        $this->assertSame([$top->id, $featured->id], $service->weeklyBestRated()->pluck('id')->all());
        $this->assertSame([$best->id], $service->weeklyBestSale()->pluck('id')->all());
        $this->assertTrue($service->weeklyBestRated()->first()->relationLoaded('firstImage'));
    }

    public function test_each_list_has_at_most_nine_products(): void
    {
        Product::factory()->count(11)->flagged('is_best')->create();

        $this->assertCount(HomeProductService::LIMIT, app(HomeProductService::class)->weeklyBestSale());
    }

    public function test_the_lists_are_cached_and_cleared_when_a_product_changes(): void
    {
        $product = Product::factory()->flagged('is_best')->create();
        $service = app(HomeProductService::class);

        $service->weeklyBestSale();
        $this->assertTrue(Cache::has(HomeProductService::CACHE_KEY));

        $product->update(['name' => 'Renamed']);
        $this->assertFalse(Cache::has(HomeProductService::CACHE_KEY));
        $this->assertSame('Renamed', $service->weeklyBestSale()->first()->name);
    }

    public function test_section_markup_follows_the_template(): void
    {
        $rated = Product::factory()->flagged('is_top')->onOffer(800)->create(['name' => 'Rated Watch', 'price' => 1000]);
        Product::factory()->flagged('is_best')->create(['name' => 'Best Bag', 'price' => 1500]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<div class="row weekly_best2" id="weekly-best-rated">', $html);
        $this->assertStringContainsString('<div class="row weekly_best2" id="weekly-best-sale">', $html);
        $this->assertStringContainsString('weekly best rated Products', $html);
        $this->assertStringContainsString('weekly best Sale Products', $html);
        // Template price markup: bare current price (bold through the template's .wsus__tk) and the old price.
        $this->assertMatchesRegularExpression('#<p class="wsus__tk d-flex align-items-center flex-nowrap">\s*₹800\.00\s*<del>₹1,000\.00</del>#', $html);
        $this->assertStringNotContainsString('wsus__tk_current', $html);
        $this->assertStringContainsString('title="'.$rated->name.'"', $html);
    }

    public function test_an_empty_list_hides_its_column_and_both_empty_hide_the_section(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('wsus__weekly_best', false);

        Product::factory()->flagged('is_best')->create();
        HomeProductService::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="weekly-best-sale"', false)
            ->assertDontSee('id="weekly-best-rated"', false)
            ->assertDontSee('weekly best rated Products');
    }
}
