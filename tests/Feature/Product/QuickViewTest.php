<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuickViewTest extends TestCase
{
    use BuildsVariants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function quickView(Product $product): string
    {
        return $this->getJson(route('frontend.products.quick-view', $product->slug))
            ->assertOk()
            ->assertJsonPath('name', $product->name)
            ->json('html');
    }

    private function variantProduct(): Product
    {
        $this->attributes();
        $product = Product::factory()->create(['has_variants' => true, 'price' => 0, 'stock_qty' => 0, 'name' => 'Linen Shirt']);

        foreach ([[$this->red, $this->small, 100, 4], [$this->blue, $this->medium, 150, 0]] as $index => [$color, $size, $price, $stock]) {
            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'sku' => 'LS-'.$index,
                'price' => $price,
                'offer_price' => null,
                'stock_qty' => $stock,
                'combination_hash' => ProductVariant::combinationHash([$color->id, $size->id]),
                'is_default' => $index === 0,
            ]);
            $variant->values()->sync([$color->id, $size->id]);
        }

        return $product->fresh();
    }

    /* ---------- Endpoint ---------- */

    public function test_a_visible_simple_product_returns_the_template_modal_body(): void
    {
        $product = Product::factory()->create([
            'name' => 'Steel Watch <b>',
            'short_description' => 'Scratch <i>resistant</i> glass.',
            'price' => 1500,
            'stock_qty' => 7,
            'video_url' => null,
        ]);

        $html = $this->quickView($product);

        $this->assertStringContainsString('class="wsus__quick_view_img"', $html);
        $this->assertStringContainsString('class="row modal_slider"', $html);
        $this->assertStringContainsString('class="wsus__pro_details_text"', $html);
        $this->assertStringContainsString('Steel Watch &lt;b&gt;', $html);
        $this->assertStringContainsString('Scratch &lt;i&gt;resistant&lt;/i&gt; glass.', $html);
        $this->assertStringContainsString('<span class="in_stock">in stock</span> (7 item)', $html);
        $this->assertStringContainsString('₹1,500.00', $html);
        $this->assertStringNotContainsString('<del>', $html);
        $this->assertStringContainsString('<span>0 review</span>', $html);
        $this->assertStringContainsString('data-product-id="'.$product->id.'"', $html);
        $this->assertStringContainsString('data-cart-add data-add-button', $html);
        $this->assertStringContainsString('data-wishlist-toggle', $html);
        $this->assertStringContainsString('data-compare-add', $html);
        $this->assertStringContainsString('class="buy_now" href="'.route('product.show', $product->slug).'">view full details', $html);
        $this->assertStringContainsString('class="select_number"', $html);
        $this->assertStringContainsString('max="7"', $html);
        $this->assertStringContainsString('class="whatsapp"', $html);

        // Left out on purpose: no video, no countdown without a dated offer, no empty dropdowns, no Instagram, no demo data.
        $this->assertStringNotContainsString('wsus__pro_det_video', $html);
        $this->assertStringNotContainsString('data-countdown-end', $html);
        $this->assertStringNotContainsString('select_2', $html);
        $this->assertStringNotContainsString('instagram', $html);
        $this->assertStringNotContainsString('$', $html);
        $this->assertStringNotContainsString('data-variant-data', $html);
    }

    public function test_offer_countdown_video_and_out_of_stock(): void
    {
        $product = Product::factory()->onOffer(800, 3)->create([
            'price' => 1000,
            'stock_qty' => 0,
            'video_url' => 'https://www.youtube.com/watch?v=abc123',
        ]);

        $html = $this->quickView($product);

        $this->assertStringContainsString('₹800.00', $html);
        $this->assertStringContainsString('<del>₹1,000.00</del>', $html);
        $this->assertStringContainsString('data-countdown-end="'.$product->offer_ends_at->toIso8601String().'"', $html);
        $this->assertStringContainsString('data-server-now=', $html);
        $this->assertStringContainsString('class="venobox wsus__pro_det_video"', $html);
        $this->assertStringContainsString('href="https://www.youtube.com/watch?v=abc123"', $html);
        $this->assertStringContainsString('<span class="in_stock">out of stock</span>', $html);
        $this->assertStringContainsString('aria-disabled="true">out of stock</a>', $html);
    }

    public function test_variant_products_carry_their_options_and_variant_data(): void
    {
        $product = $this->variantProduct();
        $default = $product->variants()->where('is_default', true)->first();

        $html = $this->quickView($product);

        $this->assertStringContainsString('class="wsus_pro_det_color"', $html);
        $this->assertStringContainsString('class="wsus_pro__det_size"', $html);
        $this->assertStringContainsString('data-color="#ff0000"', $html);
        $this->assertStringContainsString('value="'.$default->id.'" data-variant-input', $html);
        $this->assertStringContainsString('(4 item)', $html);
        $this->assertStringContainsString('data-sku>LS-0<', $html);

        preg_match('#<script type="application/json" data-variant-data>(.*?)</script>#s', $html, $match);
        $variants = collect(json_decode($match[1], true));
        $this->assertCount(2, $variants);
        $this->assertSame(100.0, (float) $variants->firstWhere('sku', 'LS-0')['current_price']);
        $this->assertFalse($variants->firstWhere('sku', 'LS-1')['in_stock']);
    }

    public function test_hidden_or_missing_products_are_a_json_404(): void
    {
        $hidden = [
            Product::factory()->pending()->create(),
            Product::factory()->inactive()->create(),
            Product::factory()->forVendor(Vendor::factory()->suspended()->create())->create(),
            Product::factory()->create(['category_id' => Category::factory()->inactive()->create()->id]),
        ];

        foreach ($hidden as $product) {
            $this->getJson(route('frontend.products.quick-view', $product->slug))->assertNotFound()->assertJsonStructure(['message']);
        }

        $this->getJson(route('frontend.products.quick-view', 'no-such-product'))->assertNotFound();
    }

    /* ---------- Cart placeholder (validation only) ---------- */

    public function test_cart_add_validates_as_json_and_stores_nothing(): void
    {
        $simple = Product::factory()->create(['price' => 250, 'stock_qty' => 3]);
        $product = $this->variantProduct();
        [$inStock, $soldOut] = [$product->variants()->where('sku', 'LS-0')->first(), $product->variants()->where('sku', 'LS-1')->first()];

        $this->postJson(route('cart.add'), ['product_id' => $simple->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['added' => false, 'quantity' => 2, 'unit_price' => 250, 'total_label' => '₹500.00'])
            ->assertJsonPath('message', 'Selected 2 × '.$simple->name.'. The cart is coming soon.');

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'product_variant_id' => $inStock->id, 'quantity' => 1])
            ->assertOk()->assertJson(['product_variant_id' => $inStock->id, 'unit_price' => 100]);

        $this->postJson(route('cart.add'), ['product_id' => $simple->id, 'quantity' => 4])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])->assertUnprocessable()->assertJsonValidationErrors('product_variant_id');
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'product_variant_id' => $soldOut->id, 'quantity' => 1])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson(route('cart.add'), ['product_id' => Product::factory()->pending()->create()->id, 'quantity' => 1])->assertUnprocessable()->assertJsonValidationErrors('product_id');
    }

    /* ---------- Cards and layout ---------- */

    public function test_cards_show_the_three_icons_and_the_right_add_to_cart_bar(): void
    {
        $category = Category::factory()->create();
        $simple = Product::factory()->create(['name' => 'Simple Lamp', 'category_id' => $category->id, 'stock_qty' => 5]);
        $soldOut = Product::factory()->create(['name' => 'Sold Out Lamp', 'category_id' => $category->id, 'stock_qty' => 0]);
        $variants = $this->variantProduct();
        $variants->update(['category_id' => $category->id]);

        $html = $this->get(route('category.show', $category->slug))->assertOk()->getContent();

        $this->assertStringContainsString('data-quick-view data-product-slug="'.$simple->slug.'"', $html);
        $this->assertStringContainsString('data-wishlist-toggle data-product-id="'.$simple->id.'"', $html);
        $this->assertStringContainsString('data-compare-add data-product-id="'.$simple->id.'"', $html);
        $this->assertStringContainsString('<i class="far fa-random"></i>', $html);
        $this->assertStringContainsString('data-cart-add data-product-id="'.$simple->id.'">add to cart</a>', $html);
        // Variant products open the quick view from the bar; sold out products get a disabled bar.
        $this->assertMatchesRegularExpression('#class="add_cart" href="[^"]*'.preg_quote($variants->slug, '#').'" role="button"\s+data-quick-view#', $html);
        $this->assertStringContainsString('aria-disabled="true" tabindex="-1">out of stock</a>', $html);
        unset($soldOut);
    }

    public function test_the_layout_has_one_modal_and_the_scripts(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="quick-view-modal"'));
        $this->assertStringContainsString('data-url="'.route('frontend.products.quick-view', ['slug' => '__SLUG__']).'"', $html);
        $this->assertStringContainsString('<meta name="cart-add-url" content="'.route('cart.add').'">', $html);
        $this->assertStringContainsString('data-quick-view-skeleton', $html);

        foreach (['cart.js', 'wishlist.js', 'compare.js', 'product-variants.js', 'quick-view.js'] as $script) {
            $this->assertStringContainsString('frontend-assets/js/'.$script, $html);
            $this->assertFileExists(public_path('frontend-assets/js/'.$script));
        }
    }
}
