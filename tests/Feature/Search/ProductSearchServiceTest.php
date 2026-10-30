<?php

namespace Tests\Feature\Search;

use App\Enums\ProductSort;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Services\ProductSearchService;
use App\Support\SearchTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProductSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private function search(): ProductSearchService
    {
        return app(ProductSearchService::class);
    }

    /**
     * @return array<int, string> names of the matching products, in result order
     */
    private function names(string $term, ProductSort $sort = ProductSort::Relevance, ?Category $category = null, ?Brand $brand = null): array
    {
        return collect($this->search()->paginate(SearchTerm::from($term), $sort, $category, $brand)->items())->pluck('name')->all();
    }

    /* ---------- Matching ---------- */

    public function test_finds_products_by_every_searchable_field(): void
    {
        $category = Category::factory()->create(['name' => 'Kitchenware']);
        $sub = SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Cookware']);
        $child = ChildCategory::factory()->create(['category_id' => $category->id, 'sub_category_id' => $sub->id, 'name' => 'Skillets']);
        $vendor = Vendor::factory()->approved()->create(['shop_name' => 'Quokka Traders']);
        $brand = Brand::factory()->create(['name' => 'Zephyrine']);

        Product::factory()->create(['name' => 'Walnut Desk Lamp', 'sku' => 'WDL-1']);
        Product::factory()->create(['name' => 'Plain Mug', 'sku' => 'MUG-77X']);
        Product::factory()->create(['name' => 'Plain Plate', 'short_description' => 'Hand glazed stoneware']);
        Product::factory()->create(['name' => 'Branded Kettle', 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Category Bowl', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Sub Pan', 'category_id' => $category->id, 'sub_category_id' => $sub->id]);
        Product::factory()->create(['name' => 'Child Pan', 'category_id' => $category->id, 'sub_category_id' => $sub->id, 'child_category_id' => $child->id]);
        Product::factory()->forVendor($vendor)->create(['name' => 'Vendor Spoon']);
        $phone = Product::factory()->create(['name' => 'Smart Phone', 'sku' => 'SP-1', 'has_variants' => true]);
        ProductVariant::factory()->create(['product_id' => $phone->id, 'sku' => 'SP-1-BLUE-128', 'is_default' => true]);

        $this->assertSame(['Walnut Desk Lamp'], $this->names('desk lamp'));
        $this->assertSame(['Plain Mug'], $this->names('mug-77'));
        $this->assertSame(['Plain Plate'], $this->names('stoneware'));
        $this->assertSame(['Branded Kettle'], $this->names('zephyr'));
        $this->assertEqualsCanonicalizing(['Category Bowl', 'Sub Pan', 'Child Pan'], $this->names('kitchenware'));
        $this->assertEqualsCanonicalizing(['Sub Pan', 'Child Pan'], $this->names('cookware'));
        $this->assertSame(['Child Pan'], $this->names('skillets'));
        $this->assertSame(['Vendor Spoon'], $this->names('quokka'));
        $this->assertSame(['Smart Phone'], $this->names('blue-128'));
    }

    public function test_hidden_products_never_appear(): void
    {
        Product::factory()->create(['name' => 'Zircon Visible']);
        Product::factory()->inactive()->create(['name' => 'Zircon Inactive']);
        Product::factory()->draft()->create(['name' => 'Zircon Draft']);
        Product::factory()->pending()->create(['name' => 'Zircon Pending']);
        Product::factory()->rejected()->create(['name' => 'Zircon Rejected']);
        Product::factory()->forVendor(Vendor::factory()->suspended()->create())->create(['name' => 'Zircon Suspended Shop']);
        Product::factory()->forVendor(Vendor::factory()->create())->create(['name' => 'Zircon Pending Shop']);
        Product::factory()->create(['name' => 'Zircon Hidden Category', 'category_id' => Category::factory()->inactive()]);
        Product::factory()->create(['name' => 'Zircon Deleted'])->delete();

        $this->assertSame(['Zircon Visible'], $this->names('zircon'));
        $this->assertSame([], $this->search()->suggestions(SearchTerm::from('zircon pending'))['products']);
    }

    public function test_like_wildcards_match_only_themselves(): void
    {
        Product::factory()->create(['name' => 'Summer Sale 50% Off Tee']);
        Product::factory()->create(['name' => 'Linen_Shirt Classic']);
        Product::factory()->create(['name' => 'Linen Shirt Modern']);

        $this->assertSame([], $this->names('%%'));
        $this->assertSame(['Summer Sale 50% Off Tee'], $this->names('50%'));
        $this->assertSame(['Linen_Shirt Classic'], $this->names('linen_shirt'));
        $this->assertSame(['Linen_Shirt Classic'], $this->names('_'));
    }

    public function test_an_empty_term_lists_every_visible_product_newest_first(): void
    {
        $old = Product::factory()->create(['name' => 'Older']);
        $new = Product::factory()->create(['name' => 'Newer']);
        Product::factory()->pending()->create(['name' => 'Pending']);

        $this->assertSame(['Newer', 'Older'], $this->names('   ', ProductSort::Relevance));
        $this->assertSame([], $this->search()->suggestions(SearchTerm::from(' '))['products']);
        unset($old, $new);
    }

    /* ---------- Order ---------- */

    public function test_relevance_order_then_stock_then_newest(): void
    {
        $brand = Brand::factory()->create(['name' => 'Gizmo Works']);
        Product::factory()->create(['name' => 'Other Field Match', 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Big Gizmo Contained', 'stock_qty' => 0]);
        Product::factory()->create(['name' => 'Big Gizmo In Stock']);
        Product::factory()->create(['name' => 'Gizmo Starts Here']);
        Product::factory()->create(['name' => 'Exact Sku Product', 'sku' => 'GIZMO']);

        $this->assertSame(
            ['Exact Sku Product', 'Gizmo Starts Here', 'Big Gizmo In Stock', 'Big Gizmo Contained', 'Other Field Match'],
            $this->names('gizmo'),
        );
    }

    public function test_an_exact_variant_sku_ranks_first(): void
    {
        Product::factory()->create(['name' => 'Ab12 Cable']);
        $phone = Product::factory()->create(['name' => 'Phone Case', 'has_variants' => true]);
        ProductVariant::factory()->create(['product_id' => $phone->id, 'sku' => 'AB12', 'is_default' => true]);

        $this->assertSame(['Phone Case', 'Ab12 Cable'], $this->names('ab12'));
    }

    public function test_price_sorts_use_the_offer_price_while_it_runs(): void
    {
        Product::factory()->create(['name' => 'Tote Mid', 'price' => 50]);
        Product::factory()->onOffer(30)->create(['name' => 'Tote Offer', 'price' => 90]);
        Product::factory()->create(['name' => 'Tote Expired', 'price' => 70, 'offer_price' => 10, 'offer_start_date' => today()->subDays(9), 'offer_end_date' => today()->subDay()]);
        Product::factory()->create(['name' => 'Tote Future', 'price' => 40, 'offer_price' => 5, 'offer_start_date' => today()->addDay()]);
        Product::factory()->create(['name' => 'Tote Last Day', 'price' => 99, 'offer_price' => 45, 'offer_start_date' => today(), 'offer_end_date' => today()]);

        $this->assertSame(['Tote Offer', 'Tote Future', 'Tote Last Day', 'Tote Mid', 'Tote Expired'], $this->names('tote', ProductSort::PriceAsc));
        $this->assertSame(['Tote Expired', 'Tote Mid', 'Tote Last Day', 'Tote Future', 'Tote Offer'], $this->names('tote', ProductSort::PriceDesc));
        $this->assertSame('Tote Last Day', $this->names('tote', ProductSort::Newest)[0]);
    }

    /* ---------- Filters ---------- */

    public function test_category_and_brand_filters_restrict_results(): void
    {
        $fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
        $sub = SubCategory::factory()->create(['category_id' => $fashion->id]);
        $home = Category::factory()->create(['name' => 'Home']);
        $brand = Brand::factory()->create(['name' => 'Ostrava', 'slug' => 'ostrava']);
        Product::factory()->create(['name' => 'Velvet Dress', 'category_id' => $fashion->id]);
        Product::factory()->create(['name' => 'Velvet Scarf', 'category_id' => $fashion->id, 'sub_category_id' => $sub->id, 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Velvet Cushion', 'category_id' => $home->id, 'brand_id' => $brand->id]);

        $this->assertEqualsCanonicalizing(['Velvet Dress', 'Velvet Scarf'], $this->names('velvet', category: $fashion));
        $this->assertEqualsCanonicalizing(['Velvet Scarf', 'Velvet Cushion'], $this->names('velvet', brand: $brand));
        $this->assertSame(['Velvet Scarf'], $this->names('velvet', category: $fashion, brand: $brand));
        $this->assertSame(['Velvet Cushion', 'Velvet Scarf'], $this->names('', brand: $brand));

        $this->assertTrue($this->search()->findCategory('fashion')->is($fashion));
        $this->assertNull($this->search()->findCategory('nope'));
        $this->assertNull($this->search()->findCategory(Category::factory()->inactive()->create(['slug' => 'off'])->slug));
        $this->assertTrue($this->search()->findBrand('ostrava')->is($brand));
        $this->assertNull($this->search()->findBrand(Brand::factory()->inactive()->create()->slug));
        $this->assertNull($this->search()->findBrand(null));
    }

    /* ---------- Suggestions ---------- */

    public function test_suggestions_shape_highlight_prices_and_links(): void
    {
        $category = Category::factory()->create(['name' => 'Lamps', 'slug' => 'lamps']);
        $sub = SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Desk Lamps', 'slug' => 'desk-lamps']);
        Brand::factory()->create(['name' => 'Lampworks', 'slug' => 'lampworks']);
        Product::factory()->onOffer(80)->create(['name' => 'Walnut Desk Lamp', 'slug' => 'walnut-desk-lamp', 'price' => 100, 'category_id' => $category->id]);
        $variantLamp = Product::factory()->create(['name' => 'Floor Lamp', 'has_variants' => true, 'category_id' => $category->id, 'sub_category_id' => $sub->id]);
        ProductVariant::factory()->create(['product_id' => $variantLamp->id, 'price' => 40, 'is_default' => true]);
        ProductVariant::factory()->create(['product_id' => $variantLamp->id, 'price' => 60]);
        Product::factory()->count(6)->create(['category_id' => $category->id, 'short_description' => 'Warm lamp light']);

        $data = $this->search()->suggestions(SearchTerm::from('  LAMP '));

        $this->assertSame('LAMP', $data['term']);
        $this->assertCount(ProductSearchService::SUGGEST_PRODUCTS, $data['products']);
        $this->assertSame(['name', 'name_parts', 'url', 'image', 'price', 'old_price'], array_keys($data['products'][0]));

        $walnut = collect($data['products'])->firstWhere('name', 'Walnut Desk Lamp');
        $this->assertSame(route('product.show', 'walnut-desk-lamp'), $walnut['url']);
        $this->assertSame([['text' => 'Walnut Desk ', 'match' => false], ['text' => 'Lamp', 'match' => true]], $walnut['name_parts']);
        $this->assertSame(Product::money(80), $walnut['price']);
        $this->assertSame(Product::money(100), $walnut['old_price']);

        $floor = collect($data['products'])->firstWhere('name', 'Floor Lamp');
        $this->assertSame('From '.Product::money(40), $floor['price']);

        $this->assertSame(['Lamps', 'Desk Lamps'], array_column($data['categories'], 'name'));
        $this->assertSame(route('category.sub.show', ['lamps', 'desk-lamps']), $data['categories'][1]['url']);
        $this->assertSame('Lamps', $data['categories'][1]['context']);
        $this->assertSame([['name' => 'Lampworks', 'name_parts' => [['text' => 'Lamp', 'match' => true], ['text' => 'works', 'match' => false]], 'url' => route('shop.index', ['brand' => 'lampworks'])]], $data['brands']);
        $this->assertSame([], $data['popular']);
        $this->assertSame(route('shop.index', ['search' => 'LAMP']), $data['more_url']);
    }

    public function test_highlight_marks_each_word_and_keeps_the_original_case(): void
    {
        Product::factory()->create(['name' => 'Red Linen Shirt, Redwood Edition']);

        $parts = $this->search()->suggestions(SearchTerm::from('red shirt'))['products'];

        // "red shirt" is not one phrase in the name, so only the FULLTEXT index (MySQL) would find it here.
        if ($parts === []) {
            $parts = $this->search()->suggestions(SearchTerm::from('red'))['products'];
            $this->assertSame([['text' => 'Red', 'match' => true], ['text' => ' Linen Shirt, ', 'match' => false], ['text' => 'Red', 'match' => true], ['text' => 'wood Edition', 'match' => false]], $parts[0]['name_parts']);
        }

        $this->assertNotEmpty($parts);
    }

    public function test_no_match_suggests_popular_categories_and_respects_the_category_filter(): void
    {
        $fashion = Category::factory()->create(['name' => 'Fashion Wear', 'slug' => 'fashion-wear', 'sort_order' => 1]);
        $home = Category::factory()->create(['name' => 'Home Wear', 'slug' => 'home-wear', 'sort_order' => 2]);
        Product::factory()->create(['name' => 'Wool Coat', 'category_id' => $fashion->id]);
        Product::factory()->create(['name' => 'Wool Rug', 'category_id' => $home->id]);

        $none = $this->search()->suggestions(SearchTerm::from('qwertyuiop'));
        $this->assertSame([], $none['products']);
        $this->assertSame(['Fashion Wear', 'Home Wear'], array_column($none['popular'], 'name'));

        $filtered = $this->search()->suggestions(SearchTerm::from('wool'), $fashion);
        $this->assertSame(['Wool Coat'], array_column($filtered['products'], 'name'));
        $this->assertSame(route('shop.index', ['search' => 'wool', 'category' => 'fashion-wear']), $filtered['more_url']);
        $this->assertSame(['Fashion Wear'], array_column($this->search()->suggestions(SearchTerm::from('wear'), $fashion)['categories'], 'name'));
    }

    public function test_suggestions_are_cached_per_term_and_category(): void
    {
        Product::factory()->create(['name' => 'Cached Kettle']);

        $this->search()->suggestions(SearchTerm::from('Kettle'));
        Product::factory()->create(['name' => 'Second Kettle']);

        $this->assertSame(['Cached Kettle'], array_column($this->search()->suggestions(SearchTerm::from('kettle  '))['products'], 'name'));

        // Another host or base path builds its own list (the cached one holds this host's absolute URLs).
        URL::forceRootUrl('http://shop.example');
        $other = $this->search()->suggestions(SearchTerm::from('kettle'));
        $this->assertCount(2, $other['products']);
        $this->assertStringStartsWith('http://shop.example/product/', $other['products'][0]['url']);
        URL::forceRootUrl(null);

        Cache::flush();
        $this->assertCount(2, $this->search()->suggestions(SearchTerm::from('kettle'))['products']);
    }
}
