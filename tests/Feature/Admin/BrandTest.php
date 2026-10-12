<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Brand;
use App\Models\User;
use App\Repositories\BrandRepository;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Northland',
            'is_featured' => 1,
            'is_active' => 1,
            'sort_order' => 3,
            'logo' => UploadedFile::fake()->image('logo.png', 300, 150),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function params(array $extra = []): array
    {
        $names = ['DT_RowIndex', 'logo', 'name', 'is_featured', 'is_active', 'sort_order', 'created_at', 'action'];

        return array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'columns' => array_map(fn (string $name) => [
                'data' => $name,
                'name' => $name,
                'searchable' => in_array($name, ['DT_RowIndex', 'logo', 'action'], true) ? 'false' : 'true',
                'orderable' => in_array($name, ['DT_RowIndex', 'logo', 'action'], true) ? 'false' : 'true',
            ], $names),
        ], $extra);
    }

    private function fetch(array $extra = []): TestResponse
    {
        // yajra keeps its request wrapper as a singleton; reset it between calls inside one test.
        $this->app->forgetInstance('datatables.request');

        return $this->admin()->getJson(route('admin.brands.data', $this->params($extra)));
    }

    /**
     * @return array<int, string>
     */
    private function names(TestResponse $response): array
    {
        return collect($response->json('data'))->pluck('name')->map(fn ($n) => html_entity_decode($n))->all();
    }

    /* ---------- Access ---------- */

    public function test_guests_are_blocked_from_every_brand_route(): void
    {
        $brand = Brand::factory()->create();

        $this->get(route('admin.brands.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.brands.create'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.brands.store'), [])->assertRedirect(route('admin.login'));
        $this->get(route('admin.brands.edit', $brand))->assertRedirect(route('admin.login'));
        $this->put(route('admin.brands.update', $brand), [])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.brands.destroy', $brand))->assertRedirect(route('admin.login'));

        $this->getJson(route('admin.brands.data'))->assertUnauthorized();
        $this->patchJson(route('admin.brands.status', $brand))->assertUnauthorized();
        $this->patchJson(route('admin.brands.featured', $brand))->assertUnauthorized();
        $this->deleteJson(route('admin.brands.destroy', $brand))->assertUnauthorized();

        $this->assertTrue($brand->fresh()->is_active);
        $this->assertFalse($brand->fresh()->is_featured);
        $this->assertNull($brand->fresh()->deleted_at);
    }

    public function test_customers_and_vendors_on_the_web_guard_are_blocked(): void
    {
        $brand = Brand::factory()->create();

        $this->actingAs(User::factory()->create(), 'web');

        $this->get(route('admin.brands.index'))->assertRedirect(route('admin.login'));
        $this->getJson(route('admin.brands.data'))->assertUnauthorized();
        $this->patchJson(route('admin.brands.status', $brand))->assertUnauthorized();
        $this->patchJson(route('admin.brands.featured', $brand))->assertUnauthorized();
        $this->deleteJson(route('admin.brands.destroy', $brand))->assertUnauthorized();
        $this->postJson(route('admin.brands.store'), $this->data())->assertUnauthorized();

        $this->assertSame(1, Brand::count());
    }

    /* ---------- Pages ---------- */

    public function test_list_page_has_a_table_and_no_filter_section(): void
    {
        $html = $this->admin()->get(route('admin.brands.index'))
            ->assertOk()
            ->assertSee('id="brands-table"', false)
            ->assertSee('data-source="'.route('admin.brands.data').'"', false)
            ->assertSee('Add Brand')
            ->getContent();

        foreach (['filter-status', 'filter-date', 'filter-reset', 'vendor-filters', 'Filters'] as $marker) {
            $this->assertStringNotContainsString($marker, $html);
        }

        $this->assertStringNotContainsString('style="', substr($html, strpos($html, 'main-content')));
        $this->assertStringNotContainsString('frontend-assets', $html);
    }

    public function test_sidebar_has_a_product_group_with_brands_highlighted(): void
    {
        $html = $this->admin()->get(route('admin.brands.index'))->getContent();

        $this->assertStringContainsString('<span>Product</span>', $html);
        $this->assertMatchesRegularExpression('/<li class="dropdown active">\s*<a href="#" class="nav-link has-dropdown"><i class="fas fa-box"><\/i> <span>Product/', $html);
        $this->assertMatchesRegularExpression('/<li class="active">\s*<a class="nav-link" href="'.preg_quote(route('admin.brands.index'), '/').'">Brands/', $html);

        // Other pages keep the group collapsed.
        $other = $this->admin()->get(route('admin.sliders.index'))->getContent();
        $this->assertStringContainsString('<li class="dropdown ">', $other);
    }

    public function test_create_page_renders_the_form_with_the_size_hint(): void
    {
        $this->admin()->get(route('admin.brands.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="logo"', false)
            ->assertSee('name="is_featured"', false)
            ->assertSee('name="is_active"', false)
            ->assertSee('name="sort_order"', false)
            ->assertSee(Brand::LOGO_HINT);
    }

    public function test_edit_page_shows_the_current_logo(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data(['name' => 'Editable']));
        $brand = Brand::firstWhere('name', 'Editable');

        $this->admin()->get(route('admin.brands.edit', $brand))
            ->assertOk()
            ->assertSee('value="Editable"', false)
            ->assertSee('storage/'.$brand->logo, false);
    }

    /* ---------- Create ---------- */

    public function test_admin_can_create_a_brand(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data())
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success');

        $brand = Brand::firstOrFail();

        $this->assertSame('Northland', $brand->name);
        $this->assertSame('northland', $brand->slug);
        $this->assertTrue($brand->is_featured);
        $this->assertTrue($brand->is_active);
        $this->assertSame(3, $brand->sort_order);
        $this->assertStringStartsWith('brands/', $brand->logo);
        $this->assertStringNotContainsString('logo.png', $brand->logo);
        Storage::disk('public')->assertExists($brand->logo);
    }

    public function test_switches_default_to_not_featured_and_sort_order_to_zero(): void
    {
        $this->admin()->post(route('admin.brands.store'), [
            'name' => 'Plain',
            'logo' => UploadedFile::fake()->image('p.png'),
        ])->assertSessionHasNoErrors();

        $brand = Brand::firstOrFail();

        $this->assertFalse($brand->is_featured);
        $this->assertFalse($brand->is_active);
        $this->assertSame(0, $brand->sort_order);
    }

    public function test_slugs_stay_unique_for_similar_names(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data(['name' => 'Acme Co']));
        $this->admin()->post(route('admin.brands.store'), $this->data(['name' => 'Acme  Co.']));

        $this->assertSame(['acme-co', 'acme-co-2'], Brand::orderBy('id')->pluck('slug')->all());
    }

    public function test_logo_is_required_on_create(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data(['logo' => null]))
            ->assertSessionHasErrors('logo');

        $this->assertSame(0, Brand::count());
    }

    public function test_invalid_and_oversized_logos_are_rejected(): void
    {
        foreach ([
            UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('huge.png')->size(2000),
        ] as $file) {
            $this->admin()->post(route('admin.brands.store'), $this->data(['logo' => $file]))
                ->assertSessionHasErrors('logo');
        }

        $this->assertSame(0, Brand::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_name_is_required_and_limited_and_sort_order_cannot_be_negative(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data(['name' => '']))->assertSessionHasErrors('name');
        $this->admin()->post(route('admin.brands.store'), $this->data(['name' => str_repeat('a', 101)]))->assertSessionHasErrors('name');
        $this->admin()->post(route('admin.brands.store'), $this->data(['sort_order' => -1]))->assertSessionHasErrors('sort_order');

        $this->assertSame(0, Brand::count());
    }

    public function test_name_must_be_unique_but_deleted_brands_do_not_block_it(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data());

        $this->admin()->post(route('admin.brands.store'), $this->data())->assertSessionHasErrors('name');
        $this->assertSame(1, Brand::count());

        Brand::firstOrFail()->delete();

        $this->admin()->post(route('admin.brands.store'), $this->data())->assertSessionHasNoErrors();
        $this->assertSame(1, Brand::count());
        $this->assertSame(2, Brand::withTrashed()->count());
        $this->assertSame(['northland', 'northland-2'], Brand::withTrashed()->orderBy('id')->pluck('slug')->all());
    }

    /* ---------- Update ---------- */

    public function test_admin_can_update_a_brand_and_keep_its_logo_when_none_is_uploaded(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data());
        $brand = Brand::firstOrFail();
        $logo = $brand->logo;

        // Saving with its own name must not trip the unique rule.
        $this->admin()->put(route('admin.brands.update', $brand), [
            'name' => 'Northland',
            'sort_order' => 9,
            'is_featured' => 0,
            'is_active' => 0,
        ])->assertRedirect(route('admin.brands.index'))->assertSessionHas('success');

        $brand->refresh();

        $this->assertSame(9, $brand->sort_order);
        $this->assertFalse($brand->is_featured);
        $this->assertFalse($brand->is_active);
        $this->assertSame($logo, $brand->logo);
        Storage::disk('public')->assertExists($logo);
    }

    public function test_renaming_to_another_brands_name_is_rejected(): void
    {
        Brand::factory()->create(['name' => 'Taken']);
        $brand = Brand::factory()->create(['name' => 'Free']);

        $this->admin()->put(route('admin.brands.update', $brand), ['name' => 'Taken'])
            ->assertSessionHasErrors('name');

        $this->assertSame('Free', $brand->fresh()->name);
    }

    public function test_replacing_the_logo_deletes_the_old_file_after_saving(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data());
        $brand = Brand::firstOrFail();
        $old = $brand->logo;

        $this->admin()->put(route('admin.brands.update', $brand), [
            'name' => 'Northland',
            'logo' => UploadedFile::fake()->image('new.webp'),
        ])->assertSessionHasNoErrors();

        $new = $brand->fresh()->logo;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_an_invalid_replacement_logo_keeps_the_old_one(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data());
        $brand = Brand::firstOrFail();

        $this->admin()->put(route('admin.brands.update', $brand), [
            'name' => 'Northland',
            'logo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('logo');

        Storage::disk('public')->assertExists($brand->logo);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_svg_logos_are_accepted(): void
    {
        $svg = UploadedFile::fake()->createWithContent('mark.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>');

        $this->admin()->post(route('admin.brands.store'), $this->data(['logo' => $svg]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Brand::count());
    }

    /* ---------- Delete ---------- */

    public function test_admin_can_delete_a_brand_over_ajax(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data());
        $brand = Brand::firstOrFail();

        $this->admin()->deleteJson(route('admin.brands.destroy', $brand))
            ->assertOk()
            ->assertJson(['message' => 'Brand deleted.']);

        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
        // Soft delete keeps the file so the brand could be restored.
        Storage::disk('public')->assertExists($brand->logo);
        $this->assertSame([], $this->names($this->fetch()));
    }

    public function test_delete_without_ajax_redirects_back_to_the_list(): void
    {
        $brand = Brand::factory()->create();

        $this->admin()->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success');
    }

    public function test_force_deleting_removes_the_logo_file(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data());
        $brand = Brand::firstOrFail();

        $brand->forceDelete();

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        Storage::disk('public')->assertMissing($brand->logo);
    }

    /* ---------- Toggles ---------- */

    public function test_status_toggle_flips_is_active_and_returns_json(): void
    {
        $brand = Brand::factory()->create(['is_active' => true]);

        $this->admin()->patchJson(route('admin.brands.status', $brand))
            ->assertOk()
            ->assertJson(['is_active' => false, 'message' => 'Brand deactivated.']);
        $this->assertFalse($brand->fresh()->is_active);

        $this->admin()->patchJson(route('admin.brands.status', $brand))
            ->assertOk()
            ->assertJson(['is_active' => true, 'message' => 'Brand activated.']);
        $this->assertTrue($brand->fresh()->is_active);
    }

    public function test_featured_toggle_flips_is_featured_and_returns_json(): void
    {
        $brand = Brand::factory()->create(['is_featured' => false]);

        $this->admin()->patchJson(route('admin.brands.featured', $brand))
            ->assertOk()
            ->assertJson(['is_featured' => true, 'message' => 'Brand marked as featured.']);
        $this->assertTrue($brand->fresh()->is_featured);
        $this->assertTrue($brand->fresh()->is_active);

        $this->admin()->patchJson(route('admin.brands.featured', $brand))
            ->assertOk()
            ->assertJson(['is_featured' => false, 'message' => 'Brand removed from featured.']);
        $this->assertFalse($brand->fresh()->is_featured);
    }

    public function test_toggling_a_missing_brand_returns_404(): void
    {
        $this->admin()->patchJson(route('admin.brands.status', 999))->assertNotFound();
        $this->admin()->patchJson(route('admin.brands.featured', 999))->assertNotFound();
    }

    /* ---------- DataTable ---------- */

    public function test_datatable_returns_json_with_the_expected_cells(): void
    {
        $brand = Brand::factory()->featured()->create(['name' => 'Aurora', 'sort_order' => 1]);

        $response = $this->fetch()->assertOk()->assertJsonPath('recordsTotal', 1);
        $row = $response->json('data.0');

        $this->assertSame(1, $row['DT_RowIndex']);
        $this->assertStringContainsString('<img', $row['logo']);
        $this->assertSame('Aurora', $row['name']);
        $this->assertSame(1, $row['sort_order']);
        $this->assertSame($brand->created_at->format('d M Y'), $row['created_at']);
        $this->assertStringContainsString(route('admin.brands.featured', $brand), $row['is_featured']);
        $this->assertStringContainsString('data-field="is_featured"', $row['is_featured']);
        $this->assertStringContainsString('checked', $row['is_featured']);
        $this->assertStringContainsString(route('admin.brands.status', $brand), $row['is_active']);
        $this->assertStringContainsString('data-field="is_active"', $row['is_active']);
        $this->assertStringContainsString(route('admin.brands.edit', $brand), $row['action']);
        $this->assertStringContainsString(route('admin.brands.destroy', $brand), $row['action']);
    }

    public function test_datatable_searches_by_name_only(): void
    {
        Brand::factory()->create(['name' => 'Aurora']);
        Brand::factory()->create(['name' => 'Pinecrest']);
        Brand::factory()->create(['name' => 'Vertex']);

        $found = $this->fetch(['search' => ['value' => 'pine']]);
        $this->assertSame(['Pinecrest'], $this->names($found));
        $found->assertJsonPath('recordsFiltered', 1)->assertJsonPath('recordsTotal', 3);

        $this->assertSame([], $this->names($this->fetch(['search' => ['value' => 'nomatch']])));
    }

    public function test_datatable_defaults_to_sort_order_and_supports_sorting_and_paging(): void
    {
        Brand::factory()->create(['name' => 'C', 'sort_order' => 3]);
        Brand::factory()->create(['name' => 'A', 'sort_order' => 1]);
        Brand::factory()->create(['name' => 'B', 'sort_order' => 2]);

        $this->assertSame(['A', 'B', 'C'], $this->names($this->fetch()));

        $this->assertSame(['C', 'B', 'A'], $this->names($this->fetch(['order' => [['column' => 2, 'dir' => 'desc']]])));

        $page = $this->fetch(['length' => 10, 'start' => 2]);
        $this->assertSame(['C'], $this->names($page));
    }

    public function test_datatable_rejects_unsupported_page_sizes(): void
    {
        $this->admin()->getJson(route('admin.brands.data', $this->params(['length' => 5000])))
            ->assertStatus(422);
    }

    public function test_datatable_excludes_deleted_brands(): void
    {
        Brand::factory()->create(['name' => 'Gone'])->delete();
        Brand::factory()->create(['name' => 'Here']);

        $this->assertSame(['Here'], $this->names($this->fetch()));
    }

    /* ---------- Frontend slider ---------- */

    public function test_only_active_and_featured_brands_show_in_the_home_slider_in_order(): void
    {
        Brand::factory()->featured()->create(['name' => 'Second Brand', 'sort_order' => 2]);
        Brand::factory()->featured()->create(['name' => 'First Brand', 'sort_order' => 1]);
        Brand::factory()->create(['name' => 'Plain Brand']);
        Brand::factory()->featured()->inactive()->create(['name' => 'Hidden Brand']);
        Brand::factory()->featured()->create(['name' => 'Deleted Brand'])->delete();

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['id="wsus__brand_sleder"', 'alt="First Brand"', 'alt="Second Brand"'], false)
            ->assertDontSee('Plain Brand')
            ->assertDontSee('Hidden Brand')
            ->assertDontSee('Deleted Brand')
            ->assertSee('loading="lazy"', false);
    }

    public function test_slider_uses_the_uploaded_logo_url(): void
    {
        $this->admin()->post(route('admin.brands.store'), $this->data(['name' => 'Uploaded']));
        $brand = Brand::firstOrFail();

        $this->get('/')->assertSee('storage/'.$brand->logo, false);
    }

    public function test_missing_logo_files_fall_back_to_the_placeholder(): void
    {
        $brand = Brand::factory()->featured()->create(['logo' => 'brands/missing.png']);

        $this->assertStringEndsWith('frontend-assets/images/brand_logo_1.jpg', $brand->logo_url);
        $this->get('/')->assertSee('frontend-assets/images/brand_logo_1.jpg', false);
    }

    public function test_the_whole_section_is_hidden_without_featured_brands(): void
    {
        Brand::factory()->create();
        Brand::factory()->featured()->inactive()->create();

        $this->get('/')->assertOk()->assertDontSee('wsus__brand_sleder', false);
    }

    /* ---------- Frontend cache ---------- */

    public function test_the_featured_brands_cache_is_filled_and_cleared_after_every_change(): void
    {
        $brand = Brand::factory()->featured()->create();

        $warm = function () {
            $this->get('/')->assertOk();
            $this->assertTrue(Cache::has(BrandRepository::CACHE_KEY));
        };

        $warm();

        // create
        $this->admin()->post(route('admin.brands.store'), $this->data(['name' => 'Created']));
        $this->assertFalse(Cache::has(BrandRepository::CACHE_KEY));
        $warm();

        // update
        $this->admin()->put(route('admin.brands.update', $brand), ['name' => 'Renamed', 'is_featured' => 1, 'is_active' => 1]);
        $this->assertFalse(Cache::has(BrandRepository::CACHE_KEY));
        $this->get('/')->assertSee('alt="Renamed"', false);

        // status toggle
        $this->admin()->patchJson(route('admin.brands.status', $brand))->assertOk();
        $this->assertFalse(Cache::has(BrandRepository::CACHE_KEY));
        $this->get('/')->assertDontSee('alt="Renamed"', false);

        // featured toggle
        $this->admin()->patchJson(route('admin.brands.status', $brand))->assertOk();
        $warm();
        $this->admin()->patchJson(route('admin.brands.featured', $brand))->assertOk();
        $this->assertFalse(Cache::has(BrandRepository::CACHE_KEY));
        $this->get('/')->assertDontSee('alt="Renamed"', false);

        // delete
        $warm();
        $this->admin()->deleteJson(route('admin.brands.destroy', $brand))->assertOk();
        $this->assertFalse(Cache::has(BrandRepository::CACHE_KEY));
    }

    /* ---------- Model ---------- */

    public function test_scopes_and_seeder(): void
    {
        Brand::factory()->featured()->create(['name' => 'B', 'sort_order' => 2]);
        Brand::factory()->featured()->create(['name' => 'A', 'sort_order' => 1]);
        Brand::factory()->inactive()->create();

        $this->assertSame(['A', 'B'], Brand::active()->featured()->ordered()->pluck('name')->all());
        $this->assertSame(3, Brand::count());

        $this->seed(BrandSeeder::class);
        $this->seed(BrandSeeder::class); // re-runnable

        $this->assertSame(9, Brand::count());
        $this->assertSame(5, Brand::where('name', '!=', 'B')->where('name', '!=', 'A')->featured()->count());
    }
}
