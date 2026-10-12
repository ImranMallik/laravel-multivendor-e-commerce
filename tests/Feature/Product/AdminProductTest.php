<?php

namespace Tests\Feature\Product;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Vendor\ProductApprovedNotification;
use App\Notifications\Vendor\ProductRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use BuildsProducts, RefreshDatabase;

    private ?Admin $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * One admin per test: auth.session ties the session to a single admin's password hash.
     */
    private function admin(): static
    {
        return $this->actingAs($this->admin ??= Admin::factory()->create(), 'admin');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function fetch(array $extra = []): TestResponse
    {
        $this->app->forgetInstance('datatables.request');

        $columns = array_map(fn (string $name) => ['data' => $name, 'name' => $name, 'searchable' => 'true', 'orderable' => 'true'],
            ['DT_RowIndex', 'thumbnail', 'name', 'vendor', 'category', 'price', 'stock_qty', 'status', 'approval_status', 'flags', 'action']);

        return $this->admin()->getJson(route('admin.products.data', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''], 'columns' => $columns,
        ], $extra)));
    }

    /**
     * @return array<int, string>
     */
    private function names(TestResponse $response): array
    {
        return collect($response->json('data'))->map(fn ($row) => trim(strtok(strip_tags($row['name']), "\n")))->all();
    }

    /* ---------- Access ---------- */

    public function test_guests_and_web_users_are_blocked(): void
    {
        $product = Product::factory()->create();

        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.products.store'), [])->assertRedirect(route('admin.login'));
        $this->getJson(route('admin.products.data'))->assertUnauthorized();

        $this->actingAs(Vendor::factory()->approved()->create()->user, 'web');
        $this->get(route('admin.products.create'))->assertRedirect(route('admin.login'));
        $this->patchJson(route('admin.products.flag', [$product, 'is_featured']))->assertUnauthorized();
        $this->postJson(route('admin.products.approve', $product))->assertUnauthorized();
        $this->deleteJson(route('admin.products.destroy', $product))->assertUnauthorized();

        $this->assertNull($product->fresh()->deleted_at);
    }

    /* ---------- Pages ---------- */

    public function test_pages_render_with_admin_assets_only(): void
    {
        $product = Product::factory()->create(['name' => 'Shown Product']);

        foreach ([route('admin.products.index'), route('admin.products.pending'), route('admin.products.create'), route('admin.products.edit', $product), route('admin.products.show', $product)] as $url) {
            $html = $this->admin()->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('frontend-assets', $html, $url);
        }

        $this->admin()->get(route('admin.products.create'))
            ->assertSee('data-product-form', false)
            ->assertSee('summernote-bs4', false)
            ->assertSee('name="is_featured"', false)
            ->assertSee('name="vendor_id"', false)
            ->assertSee(route('admin.sub-categories.child-categories', ['subCategory' => ':id']), false);

        $this->admin()->get(route('admin.products.index'))
            ->assertSee('id="filter-vendor"', false)
            ->assertSee('id="filter-category"', false)
            ->assertSee('id="filter-approval"', false)
            ->assertSee('Pending Products');
    }

    public function test_sidebar_shows_the_pending_count(): void
    {
        Product::factory()->count(2)->forVendor()->pending()->create();

        $this->admin()->get(route('admin.products.index'))->assertSee('data-pending-products-count>2<', false);
    }

    /* ---------- Create ---------- */

    public function test_admin_creates_an_approved_in_house_product(): void
    {
        $this->admin()->post(route('admin.products.store'), $this->payload([
            'is_featured' => 1,
            'is_best' => 1,
            'long_description' => '<p onclick="steal()">Hello</p><script>alert(1)</script><a href="javascript:evil()">x</a>',
        ]))->assertSessionHasNoErrors();

        $product = Product::firstOrFail();

        $this->assertNull($product->vendor_id);
        $this->assertSame(ProductApprovalStatus::Approved, $product->approval_status);
        $this->assertSame(ProductStatus::Active, $product->status);
        $this->assertTrue($product->createdBy->is($this->admin));
        $this->assertSame('galaxy-phone', $product->slug);
        $this->assertTrue($product->is_featured);
        $this->assertTrue($product->is_best);
        $this->assertFalse($product->is_top);
        $this->assertSame($this->child->id, $product->child_category_id);

        // Sanitized description: no script, event handler or javascript: link.
        $this->assertStringNotContainsString('<script', $product->long_description);
        $this->assertStringNotContainsString('onclick', $product->long_description);
        $this->assertStringNotContainsString('javascript:', $product->long_description);
        $this->assertStringContainsString('Hello', $product->long_description);

        Storage::disk('public')->assertExists($product->thumbnail);
        $this->assertStringStartsWith('products/thumbnails/', $product->thumbnail);
        $this->assertCount(2, $product->images);
        $this->assertStringStartsWith('products/gallery/', $product->images[0]->image);
        $this->assertSame([0, 1], $product->images->pluck('sort_order')->all());
        $this->assertSame([['Material', 'Aluminium']], $product->specifications->map(fn ($s) => [$s->key, $s->value])->all());

        $log = $product->approvalLogs()->sole();
        $this->assertSame('Created by admin', $log->note);
        $this->assertSame($this->admin->id, $log->admin_id);
    }

    public function test_admin_can_assign_an_approved_vendor_but_not_a_suspended_one(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $suspended = Vendor::factory()->suspended()->create();

        $this->admin()->post(route('admin.products.store'), $this->payload(['vendor_id' => $suspended->id]))->assertSessionHasErrors('vendor_id');

        $this->admin()->post(route('admin.products.store'), $this->payload(['vendor_id' => $vendor->id]))->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertSame($vendor->id, $product->vendor_id);
        $this->assertSame(ProductApprovalStatus::Approved, $product->approval_status);
    }

    public function test_category_levels_must_belong_together(): void
    {
        $this->catalog();
        $otherSub = SubCategory::factory()->create(['category_id' => Category::factory()->create()->id]);

        $this->admin()->post(route('admin.products.store'), $this->payload(['sub_category_id' => $otherSub->id]))
            ->assertSessionHasErrors(['sub_category_id' => 'The sub category must belong to the selected category.']);

        $this->admin()->post(route('admin.products.store'), $this->payload(['sub_category_id' => '', 'child_category_id' => $this->child->id]))
            ->assertSessionHasErrors('child_category_id');

        $this->assertSame(0, Product::count());
    }

    public function test_inactive_category_and_brand_are_rejected(): void
    {
        $this->admin()->post(route('admin.products.store'), $this->payload(['category_id' => Category::factory()->inactive()->create()->id, 'sub_category_id' => '', 'child_category_id' => '']))
            ->assertSessionHasErrors('category_id');
        $this->admin()->post(route('admin.products.store'), $this->payload(['brand_id' => Brand::factory()->inactive()->create()->id]))
            ->assertSessionHasErrors('brand_id');

        $this->assertSame(0, Product::count());
    }

    public function test_price_offer_and_stock_rules(): void
    {
        $cases = [
            'offer_price' => ['offer_price' => '250'],                                                    // not lower than price
            'offer_end_date' => ['offer_price' => '150', 'offer_start_date' => '2026-10-20', 'offer_end_date' => ''],
            'offer_start_date' => ['offer_price' => '150', 'offer_start_date' => '', 'offer_end_date' => '2026-10-20'],
            'stock_qty' => ['stock_qty' => -1],
            'price' => ['price' => ''],
        ];

        foreach ($cases as $field => $override) {
            $this->admin()->post(route('admin.products.store'), $this->payload($override))->assertSessionHasErrors($field);
        }

        $this->admin()->post(route('admin.products.store'), $this->payload(['offer_price' => '150', 'offer_start_date' => '2026-10-20', 'offer_end_date' => '2026-10-19']))
            ->assertSessionHasErrors('offer_end_date');

        $this->assertSame(0, Product::count());
    }

    public function test_thumbnail_sku_and_gallery_rules(): void
    {
        $this->admin()->post(route('admin.products.store'), $this->payload(['thumbnail' => null]))->assertSessionHasErrors('thumbnail');
        $this->admin()->post(route('admin.products.store'), $this->payload(['thumbnail' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))->assertSessionHasErrors('thumbnail');
        $this->admin()->post(route('admin.products.store'), $this->payload(['gallery' => array_map(fn ($i) => UploadedFile::fake()->image("g$i.jpg"), range(1, 9))]))->assertSessionHasErrors('gallery');
        $this->admin()->post(route('admin.products.store'), $this->payload(['gallery' => [UploadedFile::fake()->image('big.jpg')->size(3000)]]))->assertSessionHasErrors('gallery.0');
        $this->assertSame(0, Product::count());
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->admin()->post(route('admin.products.store'), $this->payload())->assertSessionHasNoErrors();
        $this->admin()->post(route('admin.products.store'), $this->payload(['name' => 'Another']))->assertSessionHasErrors('sku');
        $this->assertSame(1, Product::count());
    }

    /* ---------- Update & images ---------- */

    public function test_update_replaces_the_thumbnail_reorders_and_adds_gallery_images(): void
    {
        $this->admin()->post(route('admin.products.store'), $this->payload());
        $product = Product::firstOrFail();
        $oldThumb = $product->thumbnail;
        [$first, $second] = $product->images->all();

        $this->admin()->put(route('admin.products.update', $product), $this->payload([
            'name' => 'Galaxy Phone Pro',
            'thumbnail' => UploadedFile::fake()->image('new.webp'),
            'gallery' => [UploadedFile::fake()->image('g3.jpg')],
            'gallery_order' => [$second->id, $first->id],
            'specifications' => [['key' => 'Weight', 'value' => '180 g']],
        ]))->assertRedirect(route('admin.products.show', $product));

        $product->refresh();
        $this->assertSame('Galaxy Phone Pro', $product->name);
        Storage::disk('public')->assertMissing($oldThumb);
        Storage::disk('public')->assertExists($product->thumbnail);
        $this->assertSame([$second->id, $first->id], $product->images->take(2)->pluck('id')->all());
        $this->assertCount(3, $product->images);
        $this->assertSame(['Weight'], $product->specifications->pluck('key')->all());
        $this->assertSame(ProductApprovalStatus::Approved, $product->approval_status);
    }

    public function test_admin_edit_of_a_vendor_product_keeps_its_approval(): void
    {
        $this->catalog();
        $product = Product::factory()->forVendor()->create(['category_id' => $this->category->id, 'sku' => 'V-1']);

        $this->admin()->put(route('admin.products.update', $product), $this->payload(['sku' => 'V-1', 'name' => 'Renamed', 'thumbnail' => null, 'gallery' => [], 'vendor_id' => $product->vendor_id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(ProductApprovalStatus::Approved, $product->fresh()->approval_status);
        $this->assertSame('Renamed', $product->fresh()->name);
    }

    public function test_gallery_image_removal_deletes_the_file(): void
    {
        $this->admin()->post(route('admin.products.store'), $this->payload());
        $product = Product::firstOrFail();
        $image = $product->images->first();

        $this->admin()->deleteJson(route('admin.products.images.destroy', [$product, $image]))->assertOk()->assertJson(['message' => 'Image removed.']);

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->image);

        // An image of another product cannot be removed through this product's URL.
        $other = Product::factory()->create();
        $otherImage = $other->images()->create(['image' => 'products/gallery/x.jpg', 'sort_order' => 0]);
        $this->admin()->deleteJson(route('admin.products.images.destroy', [$product, $otherImage]))->assertNotFound();
    }

    public function test_soft_delete_keeps_files_and_force_delete_removes_them(): void
    {
        $this->admin()->post(route('admin.products.store'), $this->payload());
        $product = Product::firstOrFail();
        $paths = array_merge([$product->thumbnail], $product->images->pluck('image')->all());

        $this->admin()->deleteJson(route('admin.products.destroy', $product))->assertOk()->assertJson(['message' => 'Product deleted.']);
        $this->assertSoftDeleted($product);
        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $product->fresh()->forceDelete();
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    /* ---------- Toggles ---------- */

    public function test_status_and_flag_toggles_return_json(): void
    {
        $product = Product::factory()->create();

        $this->admin()->patchJson(route('admin.products.status', $product))->assertOk()->assertJson(['is_active' => false]);
        $this->assertSame(ProductStatus::Inactive, $product->fresh()->status);

        $this->admin()->patchJson(route('admin.products.flag', [$product, 'is_top']))->assertOk()->assertJson(['is_top' => true]);
        $this->assertTrue($product->fresh()->is_top);

        // Only the three known flags are routed.
        $this->assertFalse($this->admin()->patchJson('/admin/products/'.$product->id.'/flags/not_a_flag')->isSuccessful());
    }

    /* ---------- Approval ---------- */

    public function test_admin_approves_a_pending_vendor_product_and_the_vendor_is_emailed(): void
    {
        Notification::fake();
        $product = Product::factory()->forVendor()->pending()->create();

        $this->admin()->postJson(route('admin.products.approve', $product))->assertOk()->assertJson(['approval_status' => 'approved']);

        $this->assertSame(ProductApprovalStatus::Approved, $product->fresh()->approval_status);
        $this->assertSame($this->admin->id, $product->approvalLogs()->first()->admin_id);
        Notification::assertSentTo($product->vendor->user, ProductApprovedNotification::class);
    }

    public function test_reject_requires_a_reason_and_emails_it(): void
    {
        Notification::fake();
        $product = Product::factory()->forVendor()->pending()->create();

        $this->admin()->postJson(route('admin.products.reject', $product), ['reason' => ''])->assertStatus(422);
        $this->assertSame(ProductApprovalStatus::Pending, $product->fresh()->approval_status);

        $this->admin()->postJson(route('admin.products.reject', $product), ['reason' => 'Photos are blurry.'])->assertOk();

        $product->refresh();
        $this->assertSame(ProductApprovalStatus::Rejected, $product->approval_status);
        $this->assertSame('Photos are blurry.', $product->rejection_reason);
        Notification::assertSentTo($product->vendor->user, ProductRejectedNotification::class, function ($n) use ($product) {
            return str_contains(implode(' ', $n->toMail($product->vendor->user)->introLines), 'Photos are blurry.');
        });
    }

    public function test_invalid_approval_transitions_are_refused(): void
    {
        $approved = Product::factory()->create();

        $this->admin()->postJson(route('admin.products.approve', $approved))->assertStatus(422);
        $this->assertSame(ProductApprovalStatus::Approved, $approved->fresh()->approval_status);
    }

    public function test_show_page_lists_history_and_allowed_actions(): void
    {
        $product = Product::factory()->forVendor()->pending()->create();
        $product->approvalLogs()->create(['to_status' => ProductApprovalStatus::Pending, 'note' => 'Submitted by vendor']);

        $this->admin()->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertSee('Submitted by vendor')
            ->assertSee($product->vendor->shop_name)
            ->assertSee('data-product-approval="approve"', false)
            ->assertSee('data-product-approval="reject"', false);
    }

    /* ---------- DataTable ---------- */

    public function test_datatable_filters_and_search(): void
    {
        $this->catalog();
        $vendor = Vendor::factory()->approved()->create(['shop_name' => 'Zeta Store']);
        Product::factory()->create(['name' => 'House Lamp', 'sku' => 'HL-1', 'category_id' => $this->category->id]);
        Product::factory()->forVendor($vendor)->pending()->create(['name' => 'Vendor Chair', 'sku' => 'VC-9']);

        $response = $this->fetch()->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('pending', 1);
        $this->assertCount(2, $response->json('data'));

        $this->assertSame(['House Lamp'], $this->names($this->fetch(['vendor' => 'inhouse'])));
        $this->assertSame(['Vendor Chair'], $this->names($this->fetch(['vendor' => (string) $vendor->id])));
        $this->assertSame(['House Lamp'], $this->names($this->fetch(['category_id' => $this->category->id])));
        $this->assertSame(['Vendor Chair'], $this->names($this->fetch(['approval_status' => 'pending'])));

        foreach (['Lamp' => 'House Lamp', 'VC-9' => 'Vendor Chair', 'Zeta' => 'Vendor Chair'] as $term => $expected) {
            $this->assertSame([$expected], $this->names($this->fetch(['search' => ['value' => $term]])), $term);
        }

        $row = collect($this->fetch()->json('data'))->first(fn ($r) => str_contains($r['name'], 'Vendor Chair'));
        $this->assertSame('Zeta Store', $row['vendor']);
        $this->assertStringContainsString('data-product-approval="approve"', $row['action']);
        $this->assertStringContainsString('data-field="is_featured"', $row['flags']);
    }

    public function test_out_of_stock_shows_a_red_badge(): void
    {
        Product::factory()->create(['stock_qty' => 0]);

        $this->assertStringContainsString('badge-danger', $this->fetch()->json('data.0.stock_qty'));
    }

    /* ---------- Catalogue integrity ---------- */

    public function test_brands_and_categories_with_products_cannot_be_deleted(): void
    {
        $this->catalog();
        Product::factory()->create(['category_id' => $this->category->id, 'brand_id' => $this->brand->id]);

        $this->admin()->deleteJson(route('admin.brands.destroy', $this->brand))->assertStatus(422);
        $this->admin()->deleteJson(route('admin.categories.destroy', $this->category))->assertStatus(422);

        $this->assertNull($this->brand->fresh()->deleted_at);
        $this->assertNull($this->category->fresh()->deleted_at);
    }

    public function test_web_user_with_admin_route_names_cannot_reach_child_category_options(): void
    {
        $this->catalog();

        $this->actingAs(User::factory()->create(), 'web')->getJson(route('admin.sub-categories.child-categories', $this->sub))->assertUnauthorized();
        $this->admin()->getJson(route('admin.sub-categories.child-categories', $this->sub))->assertOk()->assertJsonPath('0.name', 'Smart Phones');
    }
}
