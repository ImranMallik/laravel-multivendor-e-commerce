<?php

namespace Tests\Feature\Search;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchResultsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_results_show_heading_count_cards_and_noindex(): void
    {
        Product::factory()->create(['name' => 'Walnut Desk Lamp', 'slug' => 'walnut-desk-lamp']);
        Product::factory()->create(['name' => 'Brass Floor Lamp']);
        Product::factory()->create(['name' => 'Linen Shirt']);

        $this->get(route('shop.index', ['search' => '  desk   LAMP ']))
            ->assertOk()
            ->assertSee('<title>Search: desk LAMP | '.config('app.name').'</title>', false)
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('Search results for “desk LAMP” (1 product)')
            ->assertSee(route('product.show', 'walnut-desk-lamp'), false)
            ->assertDontSee('Brass Floor Lamp')
            ->assertDontSee('Linen Shirt');

        $this->get(route('shop.index', ['search' => 'lamp']))->assertSee('Search results for “lamp” (2 products)');
    }

    public function test_the_plain_shop_page_is_unchanged_and_indexable(): void
    {
        Product::factory()->create(['name' => 'Older Product']);
        Product::factory()->create(['name' => 'Newer Product']);

        $response = $this->get(route('shop.index'))->assertOk()
            ->assertSee('<title>Shop</title>', false)
            ->assertDontSee('noindex', false)
            ->assertDontSee('Search results for')
            ->assertSeeInOrder(['Newer Product', 'Older Product'])
            ->assertDontSee('value="relevance"', false);

        $response->assertSee('value="newest" selected', false);
    }

    public function test_pagination_links_keep_the_query_string(): void
    {
        Product::factory()->count(14)->create(['short_description' => 'A sturdy canvas tote']);

        $html = $this->get(route('shop.index', ['search' => 'canvas', 'sort' => 'price_asc']))->assertOk()->getContent();

        $this->assertStringContainsString('Search results for “canvas” (14 products)', $html);
        $this->assertStringContainsString(e(route('shop.index', ['search' => 'canvas', 'sort' => 'price_asc', 'page' => 2])), $html);
        $this->assertSame(12, substr_count($html, 'class="wsus__product_item'));

        $page2 = $this->get(route('shop.index', ['search' => 'canvas', 'sort' => 'price_asc', 'page' => 2]))->assertOk()->getContent();
        $this->assertSame(2, substr_count($page2, 'class="wsus__product_item'));
        $this->assertStringContainsString(e(route('shop.index', ['search' => 'canvas', 'sort' => 'price_asc', 'page' => 1])), $page2);
    }

    public function test_sorting_and_the_sort_form(): void
    {
        Product::factory()->create(['name' => 'Tote Cheap', 'price' => 20]);
        Product::factory()->create(['name' => 'Tote Pricey', 'price' => 300]);
        Product::factory()->onOffer(10)->create(['name' => 'Tote Offer', 'price' => 500]);

        $this->get(route('shop.index', ['search' => 'tote', 'sort' => 'price_asc']))
            ->assertSeeInOrder(['Tote Offer', 'Tote Cheap', 'Tote Pricey'])
            ->assertSee('value="price_asc" selected', false)
            ->assertSee('<input type="hidden" name="search" value="tote">', false)
            ->assertSee('data-shop-sort', false);

        $this->get(route('shop.index', ['search' => 'tote', 'sort' => 'price_desc']))->assertSeeInOrder(['Tote Pricey', 'Tote Cheap', 'Tote Offer']);

        // Unknown sort: the default (best match) is used, no error.
        $this->get(route('shop.index', ['search' => 'tote', 'sort' => 'drop table']))->assertOk()->assertSee('value="relevance" selected', false);
    }

    public function test_category_and_brand_url_filters_combine_with_the_search(): void
    {
        $fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
        $brand = Brand::factory()->create(['name' => 'Ostrava', 'slug' => 'ostrava']);
        Product::factory()->create(['name' => 'Velvet Dress', 'category_id' => $fashion->id, 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Velvet Skirt', 'category_id' => $fashion->id]);
        Product::factory()->create(['name' => 'Velvet Cushion', 'brand_id' => $brand->id]);

        $this->get(route('shop.index', ['search' => 'velvet', 'category' => 'fashion']))
            ->assertSee('Velvet Dress')->assertSee('Velvet Skirt')->assertDontSee('Velvet Cushion')
            ->assertSee('<input type="hidden" name="category" value="fashion">', false);

        $this->get(route('shop.index', ['brand' => 'ostrava']))
            ->assertSee('Ostrava (2 products)')->assertSee('Velvet Dress')->assertSee('Velvet Cushion')->assertDontSee('Velvet Skirt')
            ->assertDontSee('noindex', false);

        // An unknown brand or category is ignored.
        $this->get(route('shop.index', ['search' => 'velvet', 'brand' => 'nope']))->assertSee('(3 products)');
    }

    public function test_no_results_page_suggests_popular_categories(): void
    {
        Category::factory()->create(['name' => 'Gadgets', 'slug' => 'gadgets']);

        $this->get(route('shop.index', ['search' => 'qwertyuiop']))
            ->assertOk()
            ->assertSee('Search results for “qwertyuiop” (0 products)')
            ->assertSee('No results for “qwertyuiop”.')
            ->assertSee('Popular categories')
            ->assertSee('href="'.route('category.show', 'gadgets').'">Gadgets</a>', false)
            ->assertDontSee('data-shop-sort', false);
    }

    public function test_special_characters_long_and_odd_input_are_handled(): void
    {
        Product::factory()->create(['name' => 'Plain Product']);

        $this->get(route('shop.index', ['search' => '%']))->assertOk()->assertSee('No results for “%”.');
        $this->get(route('shop.index', ['search' => '_']))->assertOk()->assertSee('(0 products)');
        $this->get(route('shop.index', ['search' => '<script>alert(1)</script>']))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

        $long = str_repeat('a', 150);
        $this->get(route('shop.index', ['search' => $long]))->assertOk()->assertSee('“'.str_repeat('a', 100).'”', false)->assertDontSee(str_repeat('a', 101));

        $this->get(route('shop.index', ['search' => '   ']))->assertOk()->assertSee('<title>Shop</title>', false)->assertSee('Plain Product');
    }
}
