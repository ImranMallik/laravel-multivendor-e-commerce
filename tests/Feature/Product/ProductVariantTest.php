<?php

namespace Tests\Feature\Product;

use App\Enums\ProductApprovalStatus;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Services\ProductApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use BuildsVariants, RefreshDatabase;

    private ?Admin $admin = null;

    private ?Vendor $vendor = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(): static
    {
        return $this->actingAs($this->admin ??= Admin::factory()->create(), 'admin');
    }

    private function seller(?Vendor $vendor = null): static
    {
        $this->vendor = $vendor ?? $this->vendor ??= Vendor::factory()->approved()->create();

        return $this->actingAs($this->vendor->user, 'web');
    }

    private function createAsAdmin(?array $rows = null, array $overrides = []): Product
    {
        $this->admin()->post(route('admin.products.store'), $this->variantPayload($rows, $overrides))->assertSessionHasNoErrors();

        return Product::latest('id')->firstOrFail();
    }

    private function approve(Product $product): void
    {
        app(ProductApprovalService::class)->approve($product, Admin::factory()->create());
    }

    /* ---------- Create ---------- */

    public function test_admin_creates_a_product_with_variants(): void
    {
        $product = $this->createAsAdmin();

        $this->assertTrue($product->has_variants);
        $this->assertCount(4, $product->variants);

        $default = $product->variants->first(); // ordered: default first
        $this->assertTrue($default->is_default);
        $this->assertSame('GP-100-Red-S', $default->sku);
        $this->assertSame('Red / S', $default->label);
        $this->assertSame(1, $product->variants->where('is_default', true)->count());

        // The product's own columns are a mirror of the cheapest active variant and the total stock.
        $this->assertSame('100.00', $product->price);
        $this->assertSame(15, $product->stock_qty);
        $this->assertNull($product->offer_price);

        $this->assertSame(
            [[$this->color->id, $this->red->id], [$this->size->id, $this->small->id]],
            $default->values->map(fn ($v) => [$v->attribute_id, $v->id])->all(),
        );

        $this->assertSame(
            $product->variants->map(fn ($v) => $v->combination_hash)->unique()->count(),
            4,
        );
    }

    public function test_vendor_creates_a_product_with_variants_for_review(): void
    {
        $this->seller()->post(route('vendor.products.store'), $this->variantPayload())->assertSessionHasNoErrors();

        $product = Product::firstOrFail();

        $this->assertSame($this->vendor->id, $product->vendor_id);
        $this->assertSame(ProductApprovalStatus::Pending, $product->approval_status);
        $this->assertCount(4, $product->variants);
    }

    public function test_a_product_without_variants_still_works_as_before(): void
    {
        $this->attributes();

        // Variant rows are ignored while the switch is off; the product's own price and stock are required.
        $this->admin()->post(route('admin.products.store'), $this->payload([
            'has_variants' => 0,
            'variants' => [$this->row($this->red, $this->small)],
        ]))->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertFalse($product->has_variants);
        $this->assertSame(0, ProductVariant::count());
        $this->assertSame('200.00', $product->current_price);
        $this->assertTrue($product->in_stock);

        $this->admin()->post(route('admin.products.store'), $this->payload(['name' => 'No Price', 'sku' => 'NP-1', 'price' => '', 'stock_qty' => '']))
            ->assertSessionHasErrors(['price', 'stock_qty']);
    }

    public function test_combination_default_and_sku_rules(): void
    {
        $this->attributes();
        $rows = fn (array $override = []) => $this->variantPayload([
            $this->row($this->red, $this->small),
            $this->row($this->red, $this->medium),
        ], $override);
        $post = fn (array $payload) => $this->admin()->post(route('admin.products.store'), $payload);

        // Same combination twice.
        $post($this->variantPayload([$this->row($this->red, $this->small), $this->row($this->red, $this->small, ['sku' => 'OTHER'])]))
            ->assertSessionHasErrors('variants.1.values');

        // Same SKU twice in the form.
        $post($this->variantPayload([$this->row($this->red, $this->small), $this->row($this->red, $this->medium, ['sku' => 'GP-100-Red-S'])]))
            ->assertSessionHasErrors('variants.1.sku');

        // The default: none chosen, or an inactive one.
        $post($rows(['variant_default' => null]))->assertSessionHasErrors('variant_default');
        $post($this->variantPayload([$this->row($this->red, $this->small, ['is_active' => 0]), $this->row($this->red, $this->medium)]))
            ->assertSessionHasErrors('variant_default');

        // No active variant at all.
        $post($this->variantPayload([$this->row($this->red, $this->small, ['is_active' => 0])]))->assertSessionHasErrors('variants');

        // No variants at all.
        $post($this->variantPayload([], ['variants' => []]))->assertSessionHasErrors('variants');

        $this->assertSame(0, Product::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_sku_must_be_unique_across_products_and_variants(): void
    {
        $this->attributes();
        Product::factory()->create(['sku' => 'TAKEN-PRODUCT']);
        $existing = $this->createAsAdmin([$this->row($this->red, $this->small)]);

        $post = fn (array $rows, array $over = []) => $this->admin()->post(route('admin.products.store'), $this->variantPayload($rows, $over + ['name' => 'Second', 'sku' => 'SECOND-1']));

        $post([$this->row($this->red, $this->small, ['sku' => 'TAKEN-PRODUCT'])])->assertSessionHasErrors('variants.0.sku');
        $post([$this->row($this->red, $this->small, ['sku' => 'GP-100-Red-S'])])->assertSessionHasErrors('variants.0.sku');
        // A variant SKU equal to its own product's SKU is refused too.
        $post([$this->row($this->red, $this->small, ['sku' => 'SECOND-1'])])->assertSessionHasErrors('variants.0.sku');
        // And a new product cannot take an existing variant's SKU.
        $this->admin()->post(route('admin.products.store'), $this->payload(['name' => 'Plain', 'sku' => 'GP-100-Red-S']))->assertSessionHasErrors('sku');

        $this->assertSame(2, Product::count());
        $this->assertSame(1, $existing->variants()->count());
    }

    public function test_row_values_must_fit_the_chosen_attributes_and_be_active(): void
    {
        $this->attributes();
        $post = fn (array $rows) => $this->admin()->post(route('admin.products.store'), $this->variantPayload($rows));

        // A size value posted under the Color attribute.
        $post([$this->row($this->red, $this->small, ['values' => [$this->color->id => $this->small->id, $this->size->id => $this->small->id]])])
            ->assertSessionHasErrors('variants.0.values');

        // One row with two attributes, another with only one.
        $post([$this->row($this->red, $this->small), $this->row($this->red, $this->medium, ['values' => [$this->color->id => $this->blue->id]])])
            ->assertSessionHasErrors('variants.1.values');

        // Inactive value.
        $this->blue->update(['is_active' => false]);
        $post([$this->row($this->blue, $this->small)])->assertSessionHasErrors('variants.0.values');

        $this->assertSame(0, Product::count());
    }

    public function test_variant_price_and_offer_rules(): void
    {
        $this->attributes();
        $post = fn (array $override) => $this->admin()->post(route('admin.products.store'), $this->variantPayload([$this->row($this->red, $this->small, $override)]));

        $post(['price' => ''])->assertSessionHasErrors('variants.0.price');
        $post(['price' => '-1'])->assertSessionHasErrors('variants.0.price');
        $post(['offer_price' => '100'])->assertSessionHasErrors('variants.0.offer_price');
        $post(['offer_price' => '150'])->assertSessionHasErrors('variants.0.offer_price');
        $post(['stock_qty' => ''])->assertSessionHasErrors('variants.0.stock_qty');
        $post(['stock_qty' => -2])->assertSessionHasErrors('variants.0.stock_qty');

        $post(['offer_price' => '80'])->assertSessionHasNoErrors();
        $this->assertSame(1, Product::count());
    }

    /* ---------- Edit ---------- */

    public function test_removed_variants_are_soft_deleted_and_restored_when_added_again(): void
    {
        $product = $this->createAsAdmin();
        $rows = $this->storedRows($product);
        $removed = $product->variants()->where('sku', 'GP-100-Blue-M')->firstOrFail();

        $keep = array_values(array_filter($rows, fn ($row) => $row['sku'] !== 'GP-100-Blue-M'));
        $this->admin()->put(route('admin.products.update', $product), $this->variantPayload($keep, ['sku' => $product->sku, 'thumbnail' => null, 'gallery' => []]))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($removed);
        $this->assertSame(3, $product->variants()->count());
        $this->assertSame(1, ProductVariant::onlyTrashed()->count());

        // Adding the combination again restores the same row (same id, SKU reusable).
        $this->admin()->put(route('admin.products.update', $product), $this->variantPayload(
            array_merge($this->storedRows($product), [$this->row($this->blue, $this->medium, ['sku' => 'GP-100-Blue-M-NEW'])]),
            ['sku' => $product->sku, 'thumbnail' => null, 'gallery' => []],
        ))->assertSessionHasNoErrors();

        $this->assertNull($removed->fresh()->deleted_at);
        $this->assertSame('GP-100-Blue-M-NEW', $removed->fresh()->sku);
        $this->assertSame(4, $product->variants()->count());
        $this->assertSame(4, ProductVariant::withTrashed()->count());
    }

    public function test_an_existing_variants_combination_cannot_be_changed(): void
    {
        $product = $this->createAsAdmin();
        $rows = $this->storedRows($product);
        $rows[0]['values'] = [$this->color->id => $this->blue->id, $this->size->id => $this->medium->id]; // another existing combination

        $this->admin()->put(route('admin.products.update', $product), $this->variantPayload($rows, ['sku' => $product->sku, 'thumbnail' => null, 'gallery' => []]))
            ->assertSessionHasErrors('variants.0.values');
    }

    public function test_the_default_can_move_and_prices_stay_in_sync_on_the_product(): void
    {
        $product = $this->createAsAdmin();
        $rows = $this->storedRows($product);
        $rows[0]['price'] = '300';             // the old cheapest variant gets expensive
        $rows[1]['price'] = '50';              // another one becomes the cheapest

        $this->admin()->put(route('admin.products.update', $product), $this->variantPayload($rows, ['sku' => $product->sku, 'variant_default' => 3, 'thumbnail' => null, 'gallery' => []]))
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('50.00', $product->price);
        $this->assertSame($rows[3]['sku'], $product->variants()->where('is_default', true)->sole()->sku);
        $this->assertSame(1, $product->variants()->where('is_default', true)->count());
    }

    public function test_switching_variants_off_makes_the_product_use_its_own_price_again(): void
    {
        $product = $this->createAsAdmin();

        $this->admin()->put(route('admin.products.update', $product), $this->payload([
            'sku' => $product->sku, 'has_variants' => 0, 'price' => '75', 'stock_qty' => 4, 'thumbnail' => null, 'gallery' => [],
        ]))->assertSessionHasNoErrors();

        $product->refresh()->unsetRelation('activeVariants');
        $this->assertFalse($product->has_variants);
        $this->assertSame('75.00', $product->current_price);
        $this->assertSame(4, $product->total_stock);
        $this->assertSame(4, $product->variants()->count(), 'the variants are kept, just not used');
    }

    /* ---------- Vendors ---------- */

    public function test_a_vendor_cannot_edit_another_vendors_variants(): void
    {
        $mine = Vendor::factory()->approved()->create();
        $other = Vendor::factory()->approved()->create();

        $this->seller($other)->post(route('vendor.products.store'), $this->variantPayload());
        $theirs = Product::firstOrFail();
        $theirRows = $this->storedRows($theirs);

        // Their product: forbidden.
        $this->seller($mine)->put(route('vendor.products.update', $theirs), $this->variantPayload($theirRows, ['sku' => $theirs->sku, 'thumbnail' => null, 'gallery' => []]))
            ->assertForbidden();

        // My product with THEIR variant id smuggled in: refused.
        $this->seller($mine)->post(route('vendor.products.store'), $this->variantPayload(null, ['name' => 'Mine', 'sku' => 'MINE-1', 'variants' => [
            $this->row($this->red, $this->small, ['sku' => 'MINE-RED-S']),
        ]]));
        $own = Product::where('sku', 'MINE-1')->firstOrFail();
        $own->update(['approval_status' => ProductApprovalStatus::Approved]);

        $smuggled = $this->storedRows($own);
        $smuggled[0]['id'] = $theirRows[0]['id'];

        $this->seller($mine)->put(route('vendor.products.update', $own), $this->variantPayload($smuggled, ['sku' => 'MINE-1', 'thumbnail' => null, 'gallery' => []]))
            ->assertSessionHasErrors('variants.0.id');

        $this->assertSame('GP-100-Red-S', ProductVariant::find($theirRows[0]['id'])->sku);
        $this->assertSame('MINE-RED-S', $own->variants()->sole()->sku);
        $this->assertSame($own->id, $own->variants()->sole()->product_id);
    }

    public function test_vendor_price_or_combination_changes_need_a_new_review_but_stock_does_not(): void
    {
        $this->seller()->post(route('vendor.products.store'), $this->variantPayload());
        $product = Product::firstOrFail();
        $this->approve($product);

        $put = fn (array $rows, array $override = []) => $this->seller()->put(route('vendor.products.update', $product), $this->variantPayload($rows, $override + ['sku' => $product->sku, 'thumbnail' => null, 'gallery' => []]));

        // Stock, SKU and the active switch: no re-approval.
        $rows = $this->storedRows($product);
        $rows[0]['stock_qty'] = 99;
        $rows[1]['sku'] = 'GP-100-RENAMED';
        $rows[2]['is_active'] = 0;
        $put($rows)->assertSessionHasNoErrors();
        $this->assertSame(ProductApprovalStatus::Approved, $product->fresh()->approval_status);

        // A variant price: back to Pending.
        $rows = $this->storedRows($product);
        $rows[0]['price'] = '105';
        $put($rows)->assertSessionHasNoErrors();
        $this->assertSame(ProductApprovalStatus::Pending, $product->fresh()->approval_status);

        // Removing a variant: reviewed.
        $this->approve($product->fresh());
        $put(array_slice($this->storedRows($product), 0, 3))->assertSessionHasNoErrors();
        $this->assertSame(ProductApprovalStatus::Pending, $product->fresh()->approval_status);

        // Adding a combination (here the removed one comes back): reviewed too.
        $this->approve($product->fresh());
        $put(array_merge($this->storedRows($product), [$this->row($this->blue, $this->medium, ['sku' => 'NEW-ONE'])]))->assertSessionHasNoErrors();
        $this->assertSame(ProductApprovalStatus::Pending, $product->fresh()->approval_status);
        // The config switch turns re-review off.
        $this->approve($product->fresh());
        config(['marketplace.vendor_product_approval' => false]);
        $rows = $this->storedRows($product);
        $rows[0]['price'] = '999';
        $put($rows)->assertSessionHasNoErrors();
        $this->assertSame(ProductApprovalStatus::Approved, $product->fresh()->approval_status);
    }

    public function test_turning_variants_on_for_an_approved_vendor_product_needs_review(): void
    {
        $this->seller()->post(route('vendor.products.store'), $this->payload());
        $product = Product::firstOrFail();
        $this->approve($product);

        $this->seller()->put(route('vendor.products.update', $product), $this->variantPayload(null, ['sku' => $product->sku, 'name' => $product->name, 'short_description' => $product->short_description, 'long_description' => $product->long_description, 'thumbnail' => null, 'gallery' => []]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($product->fresh()->has_variants);
        $this->assertSame(ProductApprovalStatus::Pending, $product->fresh()->approval_status);
    }

    /* ---------- Variant images ---------- */

    public function test_variant_images_are_stored_replaced_and_removed(): void
    {
        $rows = [
            $this->rowFor('red', 'small', ['image' => UploadedFile::fake()->image('red.jpg')]),
            $this->rowFor('red', 'medium'),
        ];
        $product = $this->createAsAdmin($rows);
        $first = $product->variants()->where('sku', 'GP-100-Red-S')->firstOrFail();

        $this->assertStringStartsWith('products/variants/', $first->image);
        Storage::disk('public')->assertExists($first->image);
        $this->assertStringContainsString($first->image, $first->image_url);
        $this->assertSame($product->thumbnail_url, $product->variants()->where('sku', 'GP-100-Red-M')->first()->setRelation('product', $product)->image_url, 'falls back to the product thumbnail');

        $old = $first->image;
        $stored = $this->storedRows($product);
        $stored[0]['image'] = UploadedFile::fake()->image('new.png');
        $this->admin()->put(route('admin.products.update', $product), $this->variantPayload($stored, ['sku' => $product->sku, 'thumbnail' => null, 'gallery' => []]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($first->fresh()->image);

        $new = $first->fresh()->image;
        $stored = $this->storedRows($product);
        $stored[0]['remove_image'] = 1;
        $this->admin()->put(route('admin.products.update', $product), $this->variantPayload($stored, ['sku' => $product->sku, 'thumbnail' => null, 'gallery' => []]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($new);
        $this->assertNull($first->fresh()->image);
    }

    public function test_invalid_variant_images_are_rejected_and_nothing_is_stored(): void
    {
        $this->attributes();

        $this->admin()->post(route('admin.products.store'), $this->variantPayload([
            $this->row($this->red, $this->small, ['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]),
        ]))->assertSessionHasErrors('variants.0.image');

        $this->assertSame(0, Product::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_failed_save_removes_the_uploaded_images(): void
    {
        $this->attributes();
        ProductVariant::creating(fn () => throw new \RuntimeException('boom'));
        $this->withoutExceptionHandling();

        try {
            $this->admin()->post(route('admin.products.store'), $this->variantPayload([
                $this->row($this->red, $this->small, ['image' => UploadedFile::fake()->image('red.jpg')]),
            ]));
            $this->fail('Expected an exception.');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(0, Product::count());
        $this->assertSame(0, ProductVariant::withTrashed()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_force_deleting_a_product_removes_its_variant_images(): void
    {
        $product = $this->createAsAdmin([$this->rowFor('red', 'small', ['image' => UploadedFile::fake()->image('red.jpg')])]);
        $path = $product->variants()->sole()->image;

        $product->variants()->sole()->delete(); // soft deleted variants keep their file until the product goes
        $product->forceDelete();

        Storage::disk('public')->assertMissing($path);
    }

    /**
     * A row by value names: rowFor('red', 'small').
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function rowFor(string $color, string $size, array $overrides = []): array
    {
        $this->attributes();

        return $this->row($this->{$color}, $this->{$size === 'small' ? 'small' : 'medium'}, $overrides);
    }
}
