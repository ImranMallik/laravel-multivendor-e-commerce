<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Slider;
use App\Models\User;
use App\Repositories\SliderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SliderTest extends TestCase
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
            'top_text' => 'new arrivals',
            'title' => 'Summer Sale',
            'offer_text' => 'start at $49.00',
            'button_text' => 'shop now',
            'button_url' => 'https://example.com/summer',
            'sort_order' => 3,
            'is_active' => 1,
            'image' => UploadedFile::fake()->image('banner.jpg', 1300, 500),
        ], $overrides);
    }

    /**
     * Query string the DataTables client sends.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function params(array $extra = []): array
    {
        $names = ['DT_RowIndex', 'image', 'title', 'button_text', 'sort_order', 'is_active', 'created_at', 'action'];

        return array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'columns' => array_map(fn (string $name) => [
                'data' => $name,
                'name' => $name,
                'searchable' => in_array($name, ['DT_RowIndex', 'image', 'action'], true) ? 'false' : 'true',
                'orderable' => in_array($name, ['DT_RowIndex', 'image', 'action'], true) ? 'false' : 'true',
            ], $names),
        ], $extra);
    }

    private function fetch(array $extra = []): TestResponse
    {
        // yajra keeps its request wrapper as a singleton; a real request boots a fresh app,
        // but several calls inside one test would otherwise replay the first call's parameters.
        $this->app->forgetInstance('datatables.request');

        return $this->admin()->getJson(route('admin.sliders.data', $this->params($extra)));
    }

    /**
     * @return array<int, string>
     */
    private function titles(TestResponse $response): array
    {
        return collect($response->json('data'))->pluck('title')->map(fn ($t) => html_entity_decode($t))->all();
    }

    /* ---------- Access ---------- */

    public function test_guests_are_blocked_from_every_slider_route(): void
    {
        $slider = Slider::factory()->create();

        $this->get(route('admin.sliders.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.sliders.create'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.sliders.store'), [])->assertRedirect(route('admin.login'));
        $this->get(route('admin.sliders.edit', $slider))->assertRedirect(route('admin.login'));
        $this->put(route('admin.sliders.update', $slider), [])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.sliders.destroy', $slider))->assertRedirect(route('admin.login'));

        $this->getJson(route('admin.sliders.data'))->assertUnauthorized();
        $this->patchJson(route('admin.sliders.status', $slider))->assertUnauthorized();
        $this->deleteJson(route('admin.sliders.destroy', $slider))->assertUnauthorized();

        $this->assertTrue($slider->fresh()->is_active);
        $this->assertNull($slider->fresh()->deleted_at);
    }

    public function test_customers_and_vendors_on_the_web_guard_are_blocked(): void
    {
        $slider = Slider::factory()->create();

        $this->actingAs(User::factory()->create(), 'web');

        $this->get(route('admin.sliders.index'))->assertRedirect(route('admin.login'));
        $this->getJson(route('admin.sliders.data'))->assertUnauthorized();
        $this->patchJson(route('admin.sliders.status', $slider))->assertUnauthorized();
        $this->deleteJson(route('admin.sliders.destroy', $slider))->assertUnauthorized();
        $this->postJson(route('admin.sliders.store'), $this->data())->assertUnauthorized();

        $this->assertSame(1, Slider::count());
    }

    /* ---------- Pages ---------- */

    public function test_list_page_has_a_table_and_no_filter_section(): void
    {
        $html = $this->admin()->get(route('admin.sliders.index'))
            ->assertOk()
            ->assertSee('id="sliders-table"', false)
            ->assertSee('data-source="'.route('admin.sliders.data').'"', false)
            ->assertSee('Add Slider')
            ->getContent();

        foreach (['filter-status', 'filter-date', 'filter-reset', 'Registered from', 'vendor-filters', 'Filters'] as $filterMarker) {
            $this->assertStringNotContainsString($filterMarker, $html);
        }

        $this->assertStringNotContainsString('style="', substr($html, strpos($html, 'main-content')));
        $this->assertStringNotContainsString('frontend-assets', $html);
    }

    public function test_sidebar_highlights_the_sliders_menu(): void
    {
        $this->admin()->get(route('admin.sliders.index'))
            ->assertSee('href="'.route('admin.sliders.index').'"', false)
            ->assertSeeInOrder(['<li class="active">', route('admin.sliders.index')], false);
    }

    public function test_create_page_renders_the_form_with_the_size_hint(): void
    {
        $this->admin()->get(route('admin.sliders.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="top_text"', false)
            ->assertSee('name="offer_text"', false)
            ->assertSee('name="button_url"', false)
            ->assertSee('name="is_active"', false)
            ->assertSee(Slider::IMAGE_HINT);
    }

    public function test_edit_page_shows_the_current_image(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data(['title' => 'Editable']));
        $slider = Slider::firstWhere('title', 'Editable');

        $this->admin()->get(route('admin.sliders.edit', $slider))
            ->assertOk()
            ->assertSee('value="Editable"', false)
            ->assertSee('storage/'.$slider->image, false);
    }

    /* ---------- Create ---------- */

    public function test_admin_can_create_a_slider(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data())
            ->assertRedirect(route('admin.sliders.index'))
            ->assertSessionHas('success');

        $slider = Slider::firstOrFail();

        $this->assertSame('Summer Sale', $slider->title);
        $this->assertSame('new arrivals', $slider->top_text);
        $this->assertSame('start at $49.00', $slider->offer_text);
        $this->assertSame('shop now', $slider->button_text);
        $this->assertSame('https://example.com/summer', $slider->button_url);
        $this->assertSame(3, $slider->sort_order);
        $this->assertTrue($slider->is_active);

        $this->assertStringStartsWith('sliders/', $slider->image);
        $this->assertStringNotContainsString('banner', basename($slider->image));
        Storage::disk('public')->assertExists($slider->image);
    }

    public function test_optional_fields_can_be_left_empty_and_sort_order_defaults_to_zero(): void
    {
        $this->admin()->post(route('admin.sliders.store'), [
            'title' => 'Minimal',
            'is_active' => 1,
            'image' => UploadedFile::fake()->image('m.png'),
        ])->assertSessionHasNoErrors();

        $slider = Slider::firstOrFail();

        $this->assertNull($slider->top_text);
        $this->assertNull($slider->button_text);
        $this->assertSame(0, $slider->sort_order);
    }

    public function test_an_off_switch_creates_an_inactive_slider(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data(['is_active' => 0]));

        $this->assertFalse(Slider::firstOrFail()->is_active);
    }

    public function test_image_is_required_on_create(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data(['image' => null]))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Slider::count());
    }

    public function test_invalid_and_oversized_images_are_rejected(): void
    {
        foreach ([
            UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->image('huge.jpg')->size(3000),
        ] as $file) {
            $this->admin()->post(route('admin.sliders.store'), $this->data(['image' => $file]))
                ->assertSessionHasErrors('image');
        }

        $this->assertSame(0, Slider::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_title_is_required_and_lengths_are_limited(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data(['title' => '']))->assertSessionHasErrors('title');
        $this->admin()->post(route('admin.sliders.store'), $this->data(['title' => str_repeat('a', 151)]))->assertSessionHasErrors('title');
        $this->admin()->post(route('admin.sliders.store'), $this->data(['top_text' => str_repeat('a', 101)]))->assertSessionHasErrors('top_text');
        $this->admin()->post(route('admin.sliders.store'), $this->data(['offer_text' => str_repeat('a', 151)]))->assertSessionHasErrors('offer_text');
        $this->admin()->post(route('admin.sliders.store'), $this->data(['sort_order' => -1]))->assertSessionHasErrors('sort_order');

        $this->assertSame(0, Slider::count());
    }

    public function test_button_text_and_url_must_be_provided_together(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data(['button_url' => null]))
            ->assertSessionHasErrors('button_url');

        $this->admin()->post(route('admin.sliders.store'), $this->data(['button_text' => null]))
            ->assertSessionHasErrors('button_text');

        $this->assertSame(0, Slider::count());

        // Both empty is fine, and so are both filled.
        $this->admin()->post(route('admin.sliders.store'), $this->data(['button_text' => null, 'button_url' => null]))
            ->assertSessionHasNoErrors();
        $this->admin()->post(route('admin.sliders.store'), $this->data(['title' => 'Second']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Slider::count());
    }

    public function test_button_url_must_be_a_web_link(): void
    {
        foreach (['not a url', 'javascript:alert(1)', 'ftp://example.com/file', '/relative/path'] as $url) {
            $this->admin()->post(route('admin.sliders.store'), $this->data(['button_url' => $url]))
                ->assertSessionHasErrors('button_url');
        }

        $this->assertSame(0, Slider::count());
    }

    /* ---------- Update ---------- */

    public function test_admin_can_update_a_slider_and_keep_its_image_when_none_is_uploaded(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data());
        $slider = Slider::firstOrFail();
        $image = $slider->image;

        $this->admin()->put(route('admin.sliders.update', $slider), [
            'title' => 'Renamed',
            'top_text' => 'hot',
            // The browser form always posts these; empty ones clear the button.
            'button_text' => '',
            'button_url' => '',
            'sort_order' => 9,
            'is_active' => 0,
        ])->assertRedirect(route('admin.sliders.index'))->assertSessionHas('success');

        $slider->refresh();

        $this->assertSame('Renamed', $slider->title);
        $this->assertSame(9, $slider->sort_order);
        $this->assertFalse($slider->is_active);
        $this->assertNull($slider->button_text);
        $this->assertSame($image, $slider->image);
        Storage::disk('public')->assertExists($image);
    }

    public function test_replacing_the_image_deletes_the_old_file_after_saving(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data());
        $slider = Slider::firstOrFail();
        $old = $slider->image;

        $this->admin()->put(route('admin.sliders.update', $slider), [
            'title' => 'Same',
            'image' => UploadedFile::fake()->image('new.webp'),
        ])->assertSessionHasNoErrors();

        $new = $slider->fresh()->image;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_an_invalid_replacement_image_keeps_the_old_one(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data());
        $slider = Slider::firstOrFail();

        $this->admin()->put(route('admin.sliders.update', $slider), [
            'title' => 'Same',
            'image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        Storage::disk('public')->assertExists($slider->image);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_update_enforces_the_button_pair_rule(): void
    {
        $slider = Slider::factory()->create();

        $this->admin()->put(route('admin.sliders.update', $slider), ['title' => 'T', 'button_text' => 'go'])
            ->assertSessionHasErrors('button_url');
    }

    /* ---------- Delete ---------- */

    public function test_admin_can_delete_a_slider_over_ajax(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data());
        $slider = Slider::firstOrFail();

        $this->admin()->deleteJson(route('admin.sliders.destroy', $slider))
            ->assertOk()
            ->assertJson(['message' => 'Slider deleted.']);

        $this->assertSoftDeleted('sliders', ['id' => $slider->id]);
        // Soft delete keeps the file so the slider could be restored.
        Storage::disk('public')->assertExists($slider->image);
        $this->assertSame([], $this->titles($this->fetch()));
    }

    public function test_delete_without_ajax_redirects_back_to_the_list(): void
    {
        $slider = Slider::factory()->create();

        $this->admin()->delete(route('admin.sliders.destroy', $slider))
            ->assertRedirect(route('admin.sliders.index'))
            ->assertSessionHas('success');
    }

    public function test_force_deleting_removes_the_image_file(): void
    {
        $this->admin()->post(route('admin.sliders.store'), $this->data());
        $slider = Slider::firstOrFail();

        $slider->forceDelete();

        $this->assertDatabaseMissing('sliders', ['id' => $slider->id]);
        Storage::disk('public')->assertMissing($slider->image);
    }

    /* ---------- Status toggle ---------- */

    public function test_status_toggle_flips_is_active_and_returns_json(): void
    {
        $slider = Slider::factory()->create(['is_active' => true]);

        $this->admin()->patchJson(route('admin.sliders.status', $slider))
            ->assertOk()
            ->assertJson(['is_active' => false, 'message' => 'Slider deactivated.']);
        $this->assertFalse($slider->fresh()->is_active);

        $this->admin()->patchJson(route('admin.sliders.status', $slider))
            ->assertOk()
            ->assertJson(['is_active' => true, 'message' => 'Slider activated.']);
        $this->assertTrue($slider->fresh()->is_active);
    }

    public function test_status_toggle_of_a_deleted_slider_is_not_found(): void
    {
        $slider = Slider::factory()->create();
        $slider->delete();

        $this->admin()->patchJson(route('admin.sliders.status', $slider->id))->assertNotFound();
    }

    /* ---------- DataTable ---------- */

    public function test_datatable_returns_json_with_the_expected_columns(): void
    {
        $slider = Slider::factory()->create(['title' => 'Table Slide', 'sort_order' => 2, 'button_text' => 'buy']);

        $this->fetch()
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.DT_RowIndex', 1)
            ->assertJsonPath('data.0.title', 'Table Slide')
            ->assertJsonPath('data.0.button_text', 'buy')
            ->assertJsonPath('data.0.sort_order', 2)
            ->assertJsonPath('data.0.created_at', $slider->created_at->format('d M Y'));

        $row = $this->fetch()->json('data.0');

        $this->assertStringContainsString('custom-switch-input', $row['is_active']);
        $this->assertStringContainsString('checked', $row['is_active']);
        $this->assertStringContainsString(route('admin.sliders.status', $slider), $row['is_active']);
        $this->assertStringContainsString(route('admin.sliders.edit', $slider), $row['action']);
        $this->assertStringContainsString('data-slider-delete', $row['action']);
        $this->assertStringContainsString('<img', $row['image']);
    }

    public function test_datatable_searches_by_title(): void
    {
        Slider::factory()->create(['title' => 'Winter Collection']);
        Slider::factory()->create(['title' => 'Summer Sale']);

        $this->assertSame(['Winter Collection'], $this->titles($this->fetch(['search' => ['value' => 'Winter']])));
        $this->assertSame(['Summer Sale'], $this->titles($this->fetch(['search' => ['value' => 'summer']])));
        $this->assertSame([], $this->titles($this->fetch(['search' => ['value' => 'nothing']])));
    }

    public function test_datatable_defaults_to_sort_order_ascending_and_can_be_sorted(): void
    {
        Slider::factory()->create(['title' => 'Third', 'sort_order' => 30]);
        Slider::factory()->create(['title' => 'First', 'sort_order' => 10]);
        Slider::factory()->create(['title' => 'Second', 'sort_order' => 20]);

        $this->assertSame(['First', 'Second', 'Third'], $this->titles($this->fetch()));
        $this->assertSame(['First', 'Second', 'Third'], $this->titles($this->fetch(['order' => [['column' => 4, 'dir' => 'asc']]])));
        $this->assertSame(['Third', 'Second', 'First'], $this->titles($this->fetch(['order' => [['column' => 4, 'dir' => 'desc']]])));
        $this->assertSame(['First', 'Second', 'Third'], $this->titles($this->fetch(['order' => [['column' => 2, 'dir' => 'asc']]])));
    }

    public function test_datatable_paginates_and_validates_the_page_length(): void
    {
        Slider::factory()->count(12)->create();

        $this->assertCount(10, $this->fetch(['length' => 10])->json('data'));
        $this->assertCount(2, $this->fetch(['length' => 10, 'start' => 10])->json('data'));
        $this->assertCount(12, $this->fetch(['length' => 25])->json('data'));

        $this->fetch(['length' => 7])->assertStatus(422)->assertJsonValidationErrors('length');
        $this->fetch(['order' => [['column' => 1, 'dir' => 'sideways']]])->assertStatus(422);
    }

    public function test_datatable_has_no_filter_parameters(): void
    {
        Slider::factory()->create(['title' => 'Active One']);
        Slider::factory()->inactive()->create(['title' => 'Inactive One']);

        // Both statuses are listed: there is no status filter on this page.
        $this->assertEqualsCanonicalizing(['Active One', 'Inactive One'], $this->titles($this->fetch()));
    }

    /* ---------- Cache ---------- */

    public function test_the_frontend_cache_is_cleared_after_create_update_toggle_and_delete(): void
    {
        $remember = fn () => Cache::put(SliderRepository::CACHE_KEY, 'stale');

        $remember();
        $this->admin()->post(route('admin.sliders.store'), $this->data());
        $this->assertFalse(Cache::has(SliderRepository::CACHE_KEY), 'create');

        $slider = Slider::firstOrFail();

        $remember();
        $this->admin()->put(route('admin.sliders.update', $slider), ['title' => 'Changed']);
        $this->assertFalse(Cache::has(SliderRepository::CACHE_KEY), 'update');

        $remember();
        $this->admin()->patchJson(route('admin.sliders.status', $slider));
        $this->assertFalse(Cache::has(SliderRepository::CACHE_KEY), 'toggle');

        $remember();
        $this->admin()->deleteJson(route('admin.sliders.destroy', $slider));
        $this->assertFalse(Cache::has(SliderRepository::CACHE_KEY), 'delete');

        $remember();
        $slider->restore();
        $this->assertFalse(Cache::has(SliderRepository::CACHE_KEY), 'restore');

        $remember();
        $slider->forceDelete();
        $this->assertFalse(Cache::has(SliderRepository::CACHE_KEY), 'force delete');
    }
}
