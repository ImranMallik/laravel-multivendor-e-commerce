<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_tabs_are_active_categories_with_visible_products_capped_at_six(): void
    {
        $categories = Category::factory()->count(8)->create();
        foreach ($categories as $index => $category) {
            // More products for the first categories, so the tab order is predictable.
            Product::factory()->count(8 - $index)->create(['category_id' => $category->id]);
        }

        $inactive = Category::factory()->inactive()->create(['name' => 'Inactive Category']);
        Product::factory()->count(9)->create(['category_id' => $inactive->id]);

        $onlyHidden = Category::factory()->create(['name' => 'Only Hidden Products']);
        Product::factory()->count(9)->pending()->create(['category_id' => $onlyHidden->id]);

        $sections = app(ProductRepository::class)->homeSections();

        $this->assertCount(ProductRepository::TOP_TABS, $sections['top_categories']);
        $this->assertSame($categories->take(6)->pluck('id')->all(), $sections['top_categories']->pluck('id')->all());
        $this->assertNotContains($inactive->id, $sections['top_categories']->pluck('id'));
        $this->assertNotContains($onlyHidden->id, $sections['top_categories']->pluck('id'));
    }

    public function test_top_products_rank_categories_and_lead_their_tab(): void
    {
        $big = Category::factory()->create();
        $small = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $big->id]);
        $top = Product::factory()->flagged('is_top')->create(['name' => 'Top Small Item', 'category_id' => $small->id]);
        Product::factory()->create(['category_id' => $small->id]);

        $sections = app(ProductRepository::class)->homeSections();

        $this->assertSame([$small->id, $big->id], $sections['top_categories']->pluck('id')->all());

        $tile = $sections['top_tiles']->first(fn (array $tile) => $tile['product']->is($top));
        $this->assertSame('top-all top-cat-'.$small->id, $tile['filters']);
    }

    public function test_each_tab_has_at_most_twelve_products_and_every_product_is_rendered_once(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(15)->create(['category_id' => $category->id]);
        Product::factory()->count(3)->flagged('is_top')->create(['category_id' => $category->id]);

        $tiles = app(ProductRepository::class)->homeSections()['top_tiles'];

        $this->assertSame(12, $tiles->filter(fn (array $tile) => str_contains($tile['filters'], 'top-cat-'.$category->id))->count());
        $this->assertSame(3, $tiles->filter(fn (array $tile) => str_contains($tile['filters'], 'top-all'))->count());
        $this->assertSame($tiles->count(), $tiles->pluck('product.id')->unique()->count());
    }

    public function test_home_page_renders_tabs_and_equal_tiles(): void
    {
        $category = Category::factory()->create(['name' => 'Cameras']);
        $product = Product::factory()->flagged('is_top')->create([
            'name' => 'Mirrorless Camera With A Very Long Product Name',
            'category_id' => $category->id,
            'price' => 1500,
            'offer_price' => 1299,
            'offer_start_date' => today()->subDay(),
            'offer_end_date' => today()->addDays(3),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Top Categories Of The Month')
            ->assertSee('data-filter=".top-all"', false)
            ->assertSee('data-filter=".top-cat-'.$category->id.'"', false)
            ->assertSee('top-all top-cat-'.$category->id, false)
            ->assertSee('<h5 class="text-truncate" title="'.e($product->name).'">', false)
            ->assertSee('class="wsus__rating"', false)
            ->assertSee('₹1,299.00')
            ->assertSee('<del>₹1,500.00</del>', false)
            ->assertSee('frontend-assets/js/top-categories.js', false);
    }

    public function test_without_top_products_the_first_category_tab_is_active(): void
    {
        $category = Category::factory()->create(['name' => 'Watches']);
        Product::factory()->create(['category_id' => $category->id]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-filter=".top-all"', false)
            ->assertSee('<button class="active" data-filter=".top-cat-'.$category->id.'">', false);
    }

    public function test_section_products_are_hidden_when_no_category_has_products(): void
    {
        Category::factory()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Top Categories Of The Month');
    }
}
