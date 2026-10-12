<?php

namespace Tests\Feature\Product;

use App\Models\Admin;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VariantStorefrontTest extends TestCase
{
    use BuildsVariants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->attributes();
    }

    /**
     * A product sold through variants. Each spec: [color value, size value, overrides].
     *
     * @param  array<int, array{0: AttributeValue, 1: AttributeValue, 2?: array<string, mixed>}>  $specs
     * @param  array<string, mixed>  $productOverrides
     */
    private function variantProduct(array $specs, array $productOverrides = []): Product
    {
        $product = Product::factory()->create(array_merge(['has_variants' => true, 'price' => 0, 'stock_qty' => 0], $productOverrides));

        foreach ($specs as $index => $spec) {
            $variant = ProductVariant::factory()->create(array_merge([
                'product_id' => $product->id,
                'sku' => 'V-'.$product->id.'-'.$index,
                'combination_hash' => ProductVariant::combinationHash([$spec[0]->id, $spec[1]->id]),
                'is_default' => $index === 0,
            ], $spec[2] ?? []));
            $variant->values()->sync([$spec[0]->id, $spec[1]->id]);
        }

        return $product->fresh();
    }

    /* ---------- Accessors ---------- */

    public function test_price_stock_and_range_come_from_the_active_variants(): void
    {
        $product = $this->variantProduct([
            [$this->red, $this->small, ['price' => 120, 'stock_qty' => 3]],
            [$this->red, $this->medium, ['price' => 100, 'stock_qty' => 0]],
            [$this->blue, $this->small, ['price' => 90, 'stock_qty' => 7, 'is_active' => false]],  // inactive: ignored everywhere
            [$this->blue, $this->medium, ['price' => 150, 'stock_qty' => 2]],
        ]);

        $this->assertSame('100.00', $product->current_price);
        $this->assertTrue($product->has_price_range);
        $this->assertSame('From ₹100.00', $product->current_price_label);
        $this->assertSame('₹100.00', $product->price_label);
        $this->assertSame('₹100.00 – ₹150.00', $product->priceRangeLabel());
        $this->assertSame(5, $product->total_stock, 'sum of the active variants');
        $this->assertTrue($product->in_stock);
        $this->assertSame(0, $product->discount_percent);

        // The default variant (first, active) is the initial selection.
        $this->assertSame('Red / S', $product->defaultVariant()->label);
    }

    public function test_equal_prices_show_no_from_and_out_of_stock_variants_make_the_product_out_of_stock(): void
    {
        $product = $this->variantProduct([
            [$this->red, $this->small, ['price' => 100, 'stock_qty' => 0]],
            [$this->red, $this->medium, ['price' => 100, 'stock_qty' => 0]],
        ]);

        $this->assertFalse($product->has_price_range);
        $this->assertSame('₹100.00', $product->current_price_label);
        $this->assertSame('₹100.00', $product->priceRangeLabel());
        $this->assertFalse($product->in_stock);
        $this->assertSame(0, $product->total_stock);
    }

    public function test_variant_offers_follow_the_products_offer_dates(): void
    {
        $open = $this->variantProduct([
            [$this->red, $this->small, ['price' => 100, 'offer_price' => 80]],
            [$this->red, $this->medium, ['price' => 130]],
        ], ['offer_start_date' => today()->subDay(), 'offer_end_date' => today()->addDays(4)]);

        $this->assertSame('80.00', $open->current_price);
        $this->assertSame(20, $open->discount_percent);
        $this->assertSame('From ₹80.00', $open->current_price_label);
        $this->assertSame('₹100.00', $open->price_label, 'the struck-through price is the cheapest variant\'s regular price');
        $this->assertTrue($open->hasValidOffer());
        $this->assertNotNull($open->offer_ends_at);

        $closed = $this->variantProduct([
            [$this->blue, $this->small, ['price' => 100, 'offer_price' => 80]],
        ], ['offer_start_date' => today()->subDays(9), 'offer_end_date' => today()->subDay()]);

        $this->assertSame('100.00', $closed->current_price);
        $this->assertSame(0, $closed->discount_percent);
        $this->assertFalse($closed->hasValidOffer());
        $this->assertNull($closed->offer_ends_at);
    }

    public function test_default_variant_and_used_attributes(): void
    {
        $product = $this->variantProduct([
            [$this->red, $this->small, ['is_active' => false]],      // the default is inactive
            [$this->blue, $this->medium],
            [$this->blue, $this->small],
        ]);

        $this->assertSame('Blue / M', $product->defaultVariant()->label, 'first active variant when the default is inactive');

        $used = $product->usedAttributes();
        $this->assertSame(['Color', 'Size'], $used->map(fn ($row) => $row['attribute']->name)->all());
        $this->assertSame(['Blue'], $used[0]['values']->pluck('value')->all(), 'only values of active variants');
        $this->assertSame(['S', 'M'], $used[1]['values']->pluck('value')->all(), 'in the attribute\'s value order');
    }

    public function test_variant_accessors(): void
    {
        $product = $this->variantProduct([[$this->red, $this->small, ['price' => 100, 'offer_price' => 75, 'stock_qty' => 2]]], [
            'offer_start_date' => today()->subDay(), 'offer_end_date' => today()->addDay(),
        ]);
        $variant = $product->variants()->with('values.attribute')->first()->setRelation('product', $product);

        $this->assertSame('Red / S', $variant->label);
        $this->assertSame('75.00', $variant->current_price);
        $this->assertSame(25, $variant->discount_percent);
        $this->assertTrue($variant->in_stock);
        $this->assertSame($product->thumbnail_url, $variant->image_url);

        $variant->update(['is_active' => false]);
        $this->assertFalse($variant->in_stock);
    }

    /* ---------- Cards & listing ---------- */

    public function test_cards_show_from_price_and_the_out_of_stock_state(): void
    {
        $this->variantProduct([
            [$this->red, $this->small, ['price' => 120]],
            [$this->red, $this->medium, ['price' => 100]],
        ], ['name' => 'Priced Shirt']);
        $this->variantProduct([
            [$this->blue, $this->small, ['stock_qty' => 0]],
            [$this->blue, $this->medium, ['stock_qty' => 0]],
        ], ['name' => 'Sold Out Shirt']);

        $html = $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertStringContainsString('From ₹100.00', $html);
        $this->assertSame(1, substr_count($html, 'out of stock'));
    }

    /* ---------- Product page ---------- */

    private function page(Product $product): TestResponse
    {
        return $this->get(route('product.show', $product->slug))->assertOk();
    }

    public function test_product_page_renders_swatches_buttons_json_and_the_default_selection(): void
    {
        $product = $this->variantProduct([
            [$this->red, $this->small, ['price' => 100, 'stock_qty' => 4]],
            [$this->red, $this->medium, ['price' => 120]],
            [$this->blue, $this->small, ['price' => 110, 'stock_qty' => 0]],
        ], ['name' => 'Option Tee']);
        $default = $product->defaultVariant();

        $html = $this->page($product)->getContent();

        // Color swatches (template colour list) and size buttons (template size list).
        $this->assertStringContainsString('class="wsus_pro_det_color"', $html);
        $this->assertStringContainsString('data-color="#ff0000"', $html);
        $this->assertStringContainsString('data-color="#0000ff"', $html);
        $this->assertStringContainsString('class="wsus_pro__det_size"', $html);
        $this->assertMatchesRegularExpression('/data-value-id="'.$this->small->id.'"[^>]*>\s*S\s*</', $html);

        // The default variant is preselected, shown and posted.
        $this->assertMatchesRegularExpression('/variant-option is-selected"[^>]*data-value-id="'.$this->red->id.'"/', $html);
        $this->assertMatchesRegularExpression('/variant-option is-selected"[^>]*data-value-id="'.$this->small->id.'"/', $html);
        $this->assertStringContainsString('name="product_variant_id" value="'.$default->id.'"', $html);
        $this->assertStringContainsString('₹100.00', $html);
        $this->assertStringContainsString('(4 item)', $html);
        $this->assertStringContainsString('data-sku>'.$default->sku.'<', $html);

        // All variants as JSON, for the browser-side selection.
        preg_match('#<script type="application/json" id="variant-data">(.*?)</script>#s', $html, $match);
        $data = json_decode($match[1], true);

        $this->assertCount(3, $data);
        $this->assertSame([$this->red->id, $this->small->id], $data[0]['values']);
        $this->assertSame('₹100.00', $data[0]['current_price_label']);
        $this->assertSame(4, $data[0]['stock_qty']);
        $this->assertFalse(collect($data)->firstWhere('sku', 'V-'.$product->id.'-2')['in_stock']);
        $this->assertTrue($data[0]['is_default']);
        $this->assertContains('image', array_keys($data[0]));

        $this->assertStringContainsString('product-variants.js', $html);
    }

    public function test_product_page_without_variants_has_no_options_but_still_an_add_to_cart_form(): void
    {
        $product = Product::factory()->create(['name' => 'Plain Item']);

        $html = $this->page($product)->getContent();

        $this->assertStringNotContainsString('variant-option', $html);
        $this->assertStringNotContainsString('variant-data', $html);
        $this->assertStringNotContainsString('product_variant_id', $html);
        $this->assertStringContainsString('action="'.route('cart.add').'"', $html);
        $this->assertStringContainsString('name="product_id" value="'.$product->id.'"', $html);
    }

    public function test_variant_images_become_gallery_slides(): void
    {
        Storage::disk('public')->put('products/variants/red.jpg', 'x');

        $product = $this->variantProduct([
            [$this->red, $this->small, ['image' => 'products/variants/red.jpg']],
            [$this->red, $this->medium],
        ]);
        $withImage = $product->variants()->where('image', 'products/variants/red.jpg')->first();

        $html = $this->page($product)->getContent();

        $this->assertSame(1, substr_count($html, 'data-variant-slide='), 'only variants that have their own image get a slide');
        $this->assertStringContainsString('data-variant-slide="'.$withImage->id.'"', $html);
        $this->assertStringContainsString('storage/products/variants/red.jpg', $html);

        preg_match('#<script type="application/json" id="variant-data">(.*?)</script>#s', $html, $match);
        $data = collect(json_decode($match[1], true));
        $this->assertStringContainsString('products/variants/red.jpg', $data->firstWhere('id', $withImage->id)['image']);
        $this->assertNull($data->firstWhere('id', '!=', $withImage->id)['image']);
    }

    public function test_inactive_variants_are_not_offered_on_the_page(): void
    {
        $product = $this->variantProduct([
            [$this->red, $this->small],
            [$this->blue, $this->small, ['is_active' => false]],
        ]);

        $html = $this->page($product)->getContent();

        $this->assertStringContainsString('data-value-id="'.$this->red->id.'"', $html);
        $this->assertStringNotContainsString('data-value-id="'.$this->blue->id.'"', $html);
    }

    /* ---------- Add to cart ---------- */

    public function test_add_to_cart_validates_that_the_variant_belongs_to_the_product(): void
    {
        $product = $this->variantProduct([[$this->red, $this->small, ['stock_qty' => 3]], [$this->red, $this->medium, ['stock_qty' => 0]]]);
        $other = $this->variantProduct([[$this->blue, $this->small]]);
        $plain = Product::factory()->create();
        $good = $product->variants()->where('stock_qty', 3)->first();

        $this->post(route('cart.add'), ['product_id' => $product->id, 'product_variant_id' => $good->id, 'quantity' => 2])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('info');

        // Another product's variant, a missing variant, a sold-out one, too many units.
        $this->post(route('cart.add'), ['product_id' => $product->id, 'product_variant_id' => $other->variants()->first()->id, 'quantity' => 1])->assertSessionHasErrors('product_variant_id');
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])->assertSessionHasErrors('product_variant_id');
        $this->post(route('cart.add'), ['product_id' => $product->id, 'product_variant_id' => $product->variants()->where('stock_qty', 0)->first()->id, 'quantity' => 1])->assertSessionHasErrors('quantity');
        $this->post(route('cart.add'), ['product_id' => $product->id, 'product_variant_id' => $good->id, 'quantity' => 4])->assertSessionHasErrors('quantity');

        // A product without variants takes none, and an invisible product cannot be added.
        $this->post(route('cart.add'), ['product_id' => $plain->id, 'product_variant_id' => $good->id, 'quantity' => 1])->assertSessionHasErrors('product_variant_id');
        $this->post(route('cart.add'), ['product_id' => $plain->id, 'quantity' => 1])->assertSessionHasNoErrors();
        $this->post(route('cart.add'), ['product_id' => Product::factory()->pending()->create()->id, 'quantity' => 1])->assertSessionHasErrors('product_id');
        $this->post(route('cart.add'), ['product_id' => $plain->id, 'quantity' => 0])->assertSessionHasErrors('quantity');
    }

    /* ---------- Caches ---------- */

    public function test_variant_and_attribute_changes_clear_the_cached_products(): void
    {
        $product = $this->variantProduct([[$this->red, $this->small]], ['name' => 'Cached Variant Item', 'is_featured' => true]);
        $variant = $product->variants()->first();

        $warm = function () {
            $this->get(route('home'))->assertOk();
            $this->assertTrue(Cache::has(ProductRepository::HOME_CACHE_KEY));
        };

        $warm();
        $variant->update(['price' => 77]);
        $this->assertFalse(Cache::has(ProductRepository::HOME_CACHE_KEY));

        $warm();
        $this->red->update(['value' => 'Crimson']);
        $this->assertFalse(Cache::has(ProductRepository::HOME_CACHE_KEY));

        $warm();
        $this->color->update(['is_active' => false]);
        $this->assertFalse(Cache::has(ProductRepository::HOME_CACHE_KEY));

        $warm();
        $variant->delete();
        $this->assertFalse(Cache::has(ProductRepository::HOME_CACHE_KEY));
    }

    public function test_home_cards_for_variant_products_cost_no_extra_queries(): void
    {
        foreach (range(1, 4) as $i) {
            $this->variantProduct([[$this->red, $this->small], [$this->blue, $this->small]], ['is_featured' => true, 'name' => "Q$i"]);
        }

        Cache::flush();
        $queries = 0;
        \DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('shop.index'))->assertOk();

        // Products, pagination count, category / sub category checks, variants: independent of the number of products.
        $this->assertLessThan(20, $queries);
    }

    /* ---------- Admin and vendor tables / forms ---------- */

    public function test_admin_table_shows_variant_count_price_range_and_total_stock(): void
    {
        $this->variantProduct([
            [$this->red, $this->small, ['price' => 100, 'stock_qty' => 3]],
            [$this->red, $this->medium, ['price' => 150, 'stock_qty' => 4]],
        ], ['name' => 'Table Tee']);
        $this->variantProduct([[$this->blue, $this->small, ['stock_qty' => 0]]], ['name' => 'Empty Tee']);

        $this->app->forgetInstance('datatables.request');
        $columns = array_map(fn (string $n) => ['data' => $n, 'name' => $n, 'searchable' => 'true', 'orderable' => 'true'],
            ['DT_RowIndex', 'thumbnail', 'name', 'vendor', 'category', 'price', 'stock_qty', 'status', 'approval_status', 'flags', 'action']);

        $rows = collect($this->actingAs(Admin::factory()->create(), 'admin')
            ->getJson(route('admin.products.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''], 'columns' => $columns]))
            ->assertOk()->json('data'));

        $tee = $rows->first(fn ($r) => str_contains($r['name'], 'Table Tee'));
        $this->assertStringContainsString('2 variants', strip_tags($tee['name']));
        $this->assertStringContainsString('₹100.00 – ₹150.00', strip_tags($tee['price']));
        $this->assertSame('7', $tee['stock_qty']);

        $empty = $rows->first(fn ($r) => str_contains($r['name'], 'Empty Tee'));
        $this->assertStringContainsString('1 variant', strip_tags($empty['name']));
        $this->assertStringContainsString('badge-danger', $empty['stock_qty']);
    }

    public function test_vendor_table_shows_variant_count_price_range_and_total_stock(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $this->variantProduct([
            [$this->red, $this->small, ['price' => 100, 'stock_qty' => 3]],
            [$this->red, $this->medium, ['price' => 150, 'stock_qty' => 4]],
        ], ['name' => 'Vendor Tee', 'vendor_id' => $vendor->id]);

        $this->app->forgetInstance('datatables.request');
        $columns = array_map(fn (string $n) => ['data' => $n, 'name' => $n, 'searchable' => 'true', 'orderable' => 'true'],
            ['DT_RowIndex', 'thumbnail', 'name', 'category', 'price', 'stock_qty', 'status', 'approval_status', 'action']);

        $row = $this->actingAs($vendor->user, 'web')
            ->getJson(route('vendor.products.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''], 'columns' => $columns]))
            ->assertOk()->json('data.0');

        $this->assertStringContainsString('2 variants', strip_tags($row['name']));
        $this->assertStringContainsString('₹100.00 – ₹150.00', strip_tags($row['price']));
        $this->assertStringContainsString('7', trim(strip_tags($row['stock_qty'])));
    }

    public function test_both_forms_render_the_variants_section_and_preload_existing_rows(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $product = $this->variantProduct([
            [$this->red, $this->small, ['price' => 100]],
            [$this->blue, $this->medium, ['price' => 120, 'is_default' => true]],
        ], ['vendor_id' => $vendor->id, 'sku' => 'FORM-1']);
        $product->variants()->update(['is_default' => false]);
        $product->variants()->where('sku', 'like', '%-1')->update(['is_default' => true]);

        $admin = $this->actingAs(Admin::factory()->create(), 'admin')->get(route('admin.products.edit', $product))->assertOk()->getContent();
        $seller = $this->actingAs($vendor->user, 'web')->get(route('vendor.products.edit', $product))->assertOk()->getContent();

        foreach ([$admin, $seller] as $html) {
            $this->assertStringContainsString('data-variants-root', $html);
            $this->assertStringContainsString('data-has-variants', $html);
            $this->assertStringContainsString('data-variants-generate', $html);
            $this->assertStringContainsString('data-attribute-name="Color"', $html);
            $this->assertStringContainsString('data-simple-pricing', $html);
            $this->assertStringContainsString('variants.js', $html);
            $this->assertSame(3, substr_count($html, '<tr data-variant-row'), 'one row per stored variant plus the JS template row');
            $this->assertStringContainsString('variants[0][sku]" value="V-'.$product->id.'-1"', $html); // the default variant is listed first
            $this->assertStringContainsString('name="variant_default" value="0" aria-label="Default variant" data-variant-default checked', $html);
        }

        $this->assertStringNotContainsString('admin-assets', $seller);
        $this->assertStringContainsString('checked', $admin);
    }

    public function test_the_forms_keep_the_posted_rows_after_a_validation_error(): void
    {
        $payload = $this->variantPayload([
            $this->row($this->red, $this->small),
            $this->row($this->red, $this->medium, ['price' => '']),   // invalid
        ], ['variant_default' => 1]);

        $html = $this->actingAs(Admin::factory()->create(), 'admin')
            ->from(route('admin.products.create'))
            ->followingRedirects()
            ->post(route('admin.products.store'), $payload)
            ->assertOk()
            ->getContent();

        $this->assertSame(3, substr_count($html, '<tr data-variant-row'), 'two posted rows plus the JS template row');
        $this->assertStringContainsString('value="GP-100-Red-M"', $html);
        $this->assertStringContainsString('Each variant needs a price.', $html);
        $this->assertStringContainsString('name="variant_default" value="1" aria-label="Default variant" data-variant-default checked', $html);
        $this->assertStringContainsString('Red / M', $html);
    }
}
