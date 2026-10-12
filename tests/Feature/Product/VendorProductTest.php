<?php

namespace Tests\Feature\Product;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ProductApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VendorProductTest extends TestCase
{
    use BuildsProducts, RefreshDatabase;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->vendor = Vendor::factory()->approved()->create(['shop_name' => 'My Shop']);
    }

    private function seller(): static
    {
        return $this->actingAs($this->vendor->user, 'web');
    }

    /**
     * A product of this vendor created through the real form (so files and logs exist).
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createViaForm(array $overrides = []): Product
    {
        $this->seller()->post(route('vendor.products.store'), $this->payload($overrides))->assertSessionHasNoErrors();

        return Product::latest('id')->firstOrFail();
    }

    private function fetch(): TestResponse
    {
        $this->app->forgetInstance('datatables.request');

        $columns = array_map(fn (string $name) => ['data' => $name, 'name' => $name, 'searchable' => 'true', 'orderable' => 'true'],
            ['DT_RowIndex', 'thumbnail', 'name', 'category', 'price', 'stock_qty', 'status', 'approval_status', 'action']);

        return $this->seller()->getJson(route('vendor.products.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''], 'columns' => $columns]));
    }

    /* ---------- Create ---------- */

    public function test_vendor_creates_a_pending_product_for_their_own_shop(): void
    {
        $other = Vendor::factory()->approved()->create();

        $product = $this->createViaForm([
            // Never accepted from a vendor:
            'vendor_id' => $other->id,
            'is_featured' => 1,
            'is_top' => 1,
            'is_best' => 1,
            'approval_status' => 'approved',
        ]);

        $this->assertSame($this->vendor->id, $product->vendor_id);
        $this->assertSame(ProductApprovalStatus::Pending, $product->approval_status);
        $this->assertFalse($product->is_featured);
        $this->assertFalse($product->is_top);
        $this->assertFalse($product->is_best);
        $this->assertTrue($product->createdBy->is($this->vendor->user));
        $this->assertSame('Submitted by vendor', $product->approvalLogs()->sole()->note);
        $this->assertCount(2, $product->images);
    }

    public function test_without_approval_vendor_products_are_approved_straight_away(): void
    {
        config(['marketplace.vendor_product_approval' => false]);

        $product = $this->createViaForm();

        $this->assertSame(ProductApprovalStatus::Approved, $product->approval_status);
    }

    public function test_pages_render_in_the_vendor_layout_without_admin_assets(): void
    {
        $product = Product::factory()->forVendor($this->vendor)->create();

        foreach ([route('vendor.products.index'), route('vendor.products.create'), route('vendor.products.edit', $product)] as $url) {
            $html = $this->seller()->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('admin-assets', $html, $url);
        }

        $this->seller()->get(route('vendor.products.create'))
            ->assertSee('data-product-form', false)
            ->assertDontSee('name="is_featured"', false)
            ->assertDontSee('name="vendor_id"', false)
            ->assertSee('summernote-lite', false);
    }

    /* ---------- Ownership ---------- */

    public function test_vendor_cannot_touch_another_vendors_product(): void
    {
        $foreign = Product::factory()->forVendor()->create(['name' => 'Not Mine']);
        $image = $foreign->images()->create(['image' => 'products/gallery/f.jpg', 'sort_order' => 0]);
        $inHouse = Product::factory()->create();

        foreach ([$foreign, $inHouse] as $product) {
            $this->seller()->get(route('vendor.products.edit', $product))->assertForbidden();
            $this->seller()->put(route('vendor.products.update', $product), $this->payload(['sku' => 'X-'.$product->id]))->assertForbidden();
            $this->seller()->deleteJson(route('vendor.products.destroy', $product))->assertForbidden();
            $this->seller()->patchJson(route('vendor.products.status', $product))->assertForbidden();
        }

        $this->seller()->deleteJson(route('vendor.products.images.destroy', [$foreign, $image]))->assertForbidden();

        $this->assertSame('Not Mine', $foreign->fresh()->name);
        $this->assertNull($foreign->fresh()->deleted_at);
        $this->assertDatabaseHas('product_images', ['id' => $image->id]);
    }

    public function test_datatable_lists_only_own_products(): void
    {
        Product::factory()->forVendor($this->vendor)->create(['name' => 'Mine One']);
        Product::factory()->forVendor()->create(['name' => 'Someone Else']);
        Product::factory()->create(['name' => 'In House']);

        $response = $this->fetch()->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->assertStringContainsString('Mine One', $response->json('data.0.name'));
    }

    public function test_pending_rejected_or_suspended_sellers_cannot_open_product_pages(): void
    {
        foreach ([Vendor::factory()->create(), Vendor::factory()->rejected()->create(), Vendor::factory()->suspended()->create()] as $vendor) {
            $this->actingAs($vendor->user, 'web')->get(route('vendor.products.index'))->assertRedirect(route('account.seller.show'));
            $this->actingAs($vendor->user, 'web')->post(route('vendor.products.store'), $this->payload())->assertRedirect(route('account.seller.show'));
        }

        $this->actingAs(User::factory()->create(), 'web')->get(route('vendor.products.create'))->assertRedirect(route('account.seller.show'));
        $this->assertSame(0, Product::count());
    }

    /* ---------- Re-review rules ---------- */

    public function test_editing_key_fields_of_an_approved_product_sends_it_back_to_pending(): void
    {
        $product = $this->createViaForm();
        app(ProductApprovalService::class)->approve($product, Admin::factory()->create());

        $this->seller()->put(route('vendor.products.update', $product), $this->payload(['price' => '180', 'thumbnail' => null, 'gallery' => []]))
            ->assertRedirect(route('vendor.products.index'));

        $product->refresh();
        $this->assertSame(ProductApprovalStatus::Pending, $product->approval_status);
        $this->assertSame('Changed by vendor after approval', $product->approvalLogs()->first()->note);
    }

    public function test_stock_or_status_only_changes_keep_the_approval(): void
    {
        $product = $this->createViaForm();
        app(ProductApprovalService::class)->approve($product, Admin::factory()->create());

        $this->seller()->put(route('vendor.products.update', $product), $this->payload(['stock_qty' => 99, 'status' => 'inactive', 'thumbnail' => null, 'gallery' => []]))
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame(ProductApprovalStatus::Approved, $product->approval_status);
        $this->assertSame(99, $product->stock_qty);
        $this->assertSame(ProductStatus::Inactive, $product->status);
    }

    public function test_the_re_review_rule_can_be_switched_off(): void
    {
        $product = $this->createViaForm();
        app(ProductApprovalService::class)->approve($product, Admin::factory()->create());
        config(['marketplace.vendor_product_approval' => false]);

        $this->seller()->put(route('vendor.products.update', $product), $this->payload(['name' => 'Renamed', 'thumbnail' => null, 'gallery' => []]));

        $this->assertSame(ProductApprovalStatus::Approved, $product->fresh()->approval_status);
    }

    public function test_removing_an_image_of_an_approved_product_sends_it_back_to_pending(): void
    {
        $product = $this->createViaForm();
        app(ProductApprovalService::class)->approve($product, Admin::factory()->create());
        $image = $product->images()->first();

        $this->seller()->deleteJson(route('vendor.products.images.destroy', [$product, $image]))->assertOk();

        Storage::disk('public')->assertMissing($image->image);
        $this->assertSame(ProductApprovalStatus::Pending, $product->fresh()->approval_status);
    }

    public function test_a_rejected_product_can_be_fixed_and_resubmitted(): void
    {
        $product = $this->createViaForm();
        app(ProductApprovalService::class)->reject($product, 'Photos are blurry.', Admin::factory()->create());

        $this->seller()->get(route('vendor.products.edit', $product))->assertOk()->assertSee('Photos are blurry.');

        $this->seller()->put(route('vendor.products.update', $product), $this->payload([
            'thumbnail' => UploadedFile::fake()->image('sharp.jpg'), 'gallery' => [],
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame(ProductApprovalStatus::Pending, $product->approval_status);
        $this->assertNull($product->rejection_reason);
        $this->assertSame('Resubmitted by vendor', $product->approvalLogs()->first()->note);
    }

    /* ---------- Status switch ---------- */

    public function test_status_switch_works_only_for_approved_products(): void
    {
        $pending = Product::factory()->forVendor($this->vendor)->pending()->create();
        $approved = Product::factory()->forVendor($this->vendor)->create();

        $this->seller()->patchJson(route('vendor.products.status', $pending))->assertForbidden();
        $this->seller()->patchJson(route('vendor.products.status', $approved))->assertOk()->assertJson(['is_active' => false]);

        $this->assertSame(ProductStatus::Inactive, $approved->fresh()->status);
    }

    public function test_vendor_can_delete_own_product(): void
    {
        $product = Product::factory()->forVendor($this->vendor)->create();

        $this->seller()->deleteJson(route('vendor.products.destroy', $product))->assertOk()->assertJson(['message' => 'Product deleted.']);

        $this->assertSoftDeleted($product);
    }

    public function test_vendor_category_option_endpoints_work(): void
    {
        $this->catalog();

        $this->seller()->getJson(route('vendor.categories.sub-categories', $this->category))->assertOk()->assertJsonPath('0.name', 'Phones');
        $this->seller()->getJson(route('vendor.sub-categories.child-categories', $this->sub))->assertOk()->assertJsonPath('0.name', 'Smart Phones');
    }
}
