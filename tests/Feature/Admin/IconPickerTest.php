<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Rules\FontAwesomeIcon;
use App\Services\FontAwesomeIcons;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IconPickerTest extends TestCase
{
    use RefreshDatabase;

    private ?Admin $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(): static
    {
        return $this->actingAs($this->admin ??= Admin::factory()->create(), 'admin');
    }

    /**
     * @return array{version: string, icons: array<int, array{c: string, n: string, l: string, t: string, s: string}>}
     */
    private function iconFile(): array
    {
        return json_decode(file_get_contents(public_path(FontAwesomeIcons::PATH)), true);
    }

    private function fetch(): TestResponse
    {
        // yajra keeps its request wrapper as a singleton; reset it so each call uses its own parameters.
        $this->app->forgetInstance('datatables.request');

        $columns = ['DT_RowIndex', 'visual', 'name', 'sub_categories_count', 'sort_order', 'is_active', 'action'];

        return $this->admin()->getJson(route('admin.categories.data', [
            'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''],
            'columns' => array_map(fn ($c) => ['data' => $c, 'name' => $c, 'searchable' => 'true', 'orderable' => 'true'], $columns),
        ]));
    }

    /* =====================  The icon list  ===================== */

    public function test_the_icon_list_is_font_awesome_free_5_15_1_and_complete(): void
    {
        $file = $this->iconFile();
        $perStyle = collect($file['icons'])->countBy('t');

        $this->assertSame('5.15.1', $file['version']);
        $this->assertSame(1002, $perStyle['solid']);
        $this->assertSame(152, $perStyle['regular']);
        $this->assertSame(458, $perStyle['brands']);
        $this->assertCount(1612, $file['icons']);
        $this->assertCount(1612, array_unique(array_column($file['icons'], 'c')), 'every class is unique');
    }

    public function test_every_entry_is_well_formed_and_uses_only_free_styles(): void
    {
        $prefixes = ['solid' => 'fas', 'regular' => 'far', 'brands' => 'fab'];

        foreach ($this->iconFile()['icons'] as $icon) {
            $this->assertSame(['c', 'n', 'l', 't', 's'], array_keys($icon));
            $this->assertArrayHasKey($icon['t'], $prefixes, $icon['c']);
            $this->assertSame($prefixes[$icon['t']].' fa-'.$icon['n'], $icon['c']);
            $this->assertNotSame('', $icon['l']);
        }
    }

    public function test_every_listed_icon_is_defined_in_the_font_awesome_css_the_pages_load(): void
    {
        $css = file_get_contents(public_path('admin-assets/modules/fontawesome-5.15.1/css/all.min.css'));

        $this->assertStringContainsString('Font Awesome Free 5.15.1', $css);

        $missing = collect($this->iconFile()['icons'])
            ->reject(fn ($icon) => str_contains($css, '.fa-'.$icon['n'].':before'))
            ->pluck('c')
            ->all();

        $this->assertSame([], $missing, 'Icons offered by the picker that the CSS does not define');
    }

    public function test_the_admin_copy_of_font_awesome_matches_the_storefronts_version(): void
    {
        $this->assertSame(
            md5_file(public_path('frontend-assets/css/all.min.css')),
            md5_file(public_path('admin-assets/modules/fontawesome-5.15.1/css/all.min.css')),
            'The admin picker must use the same Font Awesome release as the storefront',
        );
    }

    public function test_pro_light_and_missing_style_combinations_are_not_offered(): void
    {
        $classes = collect($this->iconFile()['icons'])->pluck('c');

        $this->assertTrue($classes->contains('fas fa-tv'));
        $this->assertTrue($classes->contains('far fa-star'));
        $this->assertTrue($classes->contains('fab fa-apple'));

        foreach (['fal fa-tshirt', 'fad fa-tv', 'fat fa-tv', 'fas fa-chair-office', 'fas fa-home-lg-alt', 'far fa-tv', 'far fa-camera'] as $unavailable) {
            $this->assertFalse($classes->contains($unavailable), $unavailable);
        }

        $this->assertSame(0, $classes->filter(fn ($c) => preg_match('/^(fal|fad|fat|fa) /', $c))->count());
    }

    public function test_the_service_reads_the_same_list(): void
    {
        $service = app(FontAwesomeIcons::class);

        $this->assertSame(1612, $service->count());
        $this->assertTrue($service->exists('fas fa-tv'));
        $this->assertFalse($service->exists('fas fa-chair-office'));
        $this->assertFalse($service->exists(''));
    }

    /* =====================  Validation rule  ===================== */

    public function test_the_rule_accepts_only_exact_picker_values(): void
    {
        $rule = new FontAwesomeIcon;
        $passes = fn ($value) => Validator::make(['icon' => $value], ['icon' => ['nullable', 'string', $rule]])->passes();

        foreach (['fas fa-tv', 'far fa-star', 'fab fa-apple', 'fas fa-mobile-alt', 'fas fa-th-large', null, ''] as $valid) {
            $this->assertTrue($passes($valid), var_export($valid, true));
        }

        foreach ([
            'fal fa-tshirt', 'fas fa-chair-office', 'far fa-tv', 'far fa-camera', 'fa-solid fa-tv', 'fa fa-home',
            'fas fa-tv fa-2x', 'fas  fa-tv', ' fas fa-tv', 'FAS FA-TV', 'fas fa-tv; x', 'fas fa-', 'fas', 'tv', '<script>',
            'fas fa-does-not-exist', ['fas fa-tv'],
        ] as $invalid) {
            $this->assertFalse($passes($invalid), var_export($invalid, true));
        }
    }

    public function test_a_valid_icon_saves_the_full_class_string(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => 'Telly', 'icon' => 'fas fa-tv'])
            ->assertSessionHasNoErrors();

        $this->assertSame('fas fa-tv', Category::firstWhere('name', 'Telly')->icon);

        foreach (['far fa-star', 'fab fa-apple'] as $icon) {
            $this->admin()->post(route('admin.categories.store'), ['name' => 'Icon '.$icon, 'icon' => $icon])->assertSessionHasNoErrors();
        }
    }

    public function test_invalid_and_hand_edited_icons_are_rejected_with_a_clear_message(): void
    {
        foreach (['fal fa-tshirt', 'fas fa-chair-office', 'far fa-tv', 'fa-solid fa-tv', 'fas fa-tv fa-2x', 'not an icon', 'fas fa-does-not-exist'] as $icon) {
            $this->admin()->post(route('admin.categories.store'), ['name' => 'Bad', 'icon' => $icon])
                ->assertSessionHasErrors(['icon' => 'The selected icon is not available. Please choose one from the icon picker.']);
        }

        $this->admin()->post(route('admin.categories.store'), ['name' => 'Bad', 'icon' => str_repeat('a', 101)])->assertSessionHasErrors('icon');

        $this->assertSame(0, Category::count());
    }

    public function test_a_rejected_edit_keeps_the_saved_icon_and_a_cleared_icon_is_stored_as_null(): void
    {
        $category = Category::factory()->create(['name' => 'Editable', 'icon' => 'fas fa-tv']);

        $this->admin()->put(route('admin.categories.update', $category), ['name' => 'Editable', 'icon' => 'fal fa-tshirt'])
            ->assertSessionHasErrors('icon');
        $this->assertSame('fas fa-tv', $category->fresh()->icon);

        $this->admin()->put(route('admin.categories.update', $category), ['name' => 'Editable', 'icon' => 'fas fa-gamepad'])
            ->assertSessionHasNoErrors();
        $this->assertSame('fas fa-gamepad', $category->fresh()->icon);

        // "Clear" in the picker sends an empty value.
        $this->admin()->put(route('admin.categories.update', $category), ['name' => 'Editable', 'icon' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($category->fresh()->icon);
    }

    /* =====================  The form  ===================== */

    public function test_the_category_form_uses_the_picker_instead_of_a_text_field(): void
    {
        $html = $this->admin()->get(route('admin.categories.create'))->assertOk()->getContent();

        $this->assertStringContainsString('data-icon-picker', $html);
        $this->assertStringContainsString('name="icon" value=""', $html);
        $this->assertStringContainsString('Choose icon', $html);
        $this->assertStringContainsString('aria-label="Clear icon"', $html);
        $this->assertStringContainsString('No icon selected', $html);

        // The old free-text field and its preview are gone.
        $this->assertStringNotContainsString('Font Awesome classes', $html);
        $this->assertStringNotContainsString('data-icon-preview="icon-preview"', $html);
        $this->assertStringNotContainsString('placeholder="fas fa-tv"', $html);

        // One modal, one script, one stylesheet, and the admin never touches storefront assets.
        $this->assertSame(1, substr_count($html, 'id="icon-picker-modal"'));
        $this->assertSame(1, substr_count($html, 'admin-assets/js/icon-picker.js'));
        $this->assertSame(1, substr_count($html, 'admin-assets/modules/fontawesome-5.15.1/css/all.min.css'));
        $this->assertStringContainsString('admin-assets/data/fontawesome-icons.json', $html);
        $this->assertStringNotContainsString('frontend-assets', $html);
    }

    public function test_the_modal_has_search_style_tabs_and_is_accessible(): void
    {
        $html = $this->admin()->get(route('admin.categories.create'))->getContent();

        foreach (['data-icon-search', 'aria-label="Search icons"', 'data-icon-style="all"', 'data-icon-style="solid"', 'data-icon-style="regular"', 'data-icon-style="brands"', 'data-icon-grid', 'role="dialog"', 'aria-labelledby="icon-picker-title"', 'data-dismiss="modal"'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }

        // The grid is built from the JSON by script: the icons are not in the page HTML.
        $this->assertLessThan(5, substr_count($html, 'data-icon-class'));
        $this->assertLessThan(150000, strlen($html));
    }

    public function test_the_modal_sits_outside_every_form(): void
    {
        $html = $this->admin()->get(route('admin.categories.create'))->getContent();
        $before = substr($html, 0, strpos($html, 'id="icon-picker-modal"'));

        $this->assertSame(preg_match_all('/<form[\s>]/', $before), preg_match_all('/<\/form>/', $before), 'the modal must not be inside a <form>');
    }

    public function test_the_edit_form_preloads_the_saved_icon(): void
    {
        $category = Category::factory()->create(['icon' => 'fas fa-tv']);

        $html = $this->admin()->get(route('admin.categories.edit', $category))->assertOk()->getContent();

        $this->assertStringContainsString('name="icon" value="fas fa-tv"', $html);
        $this->assertMatchesRegularExpression('#<i data-icon-preview class="fas fa-tv"></i>#', $html);
        $this->assertMatchesRegularExpression('#data-icon-display[^>]*value="fas fa-tv"#', $html);
        $this->assertStringNotContainsString('data-icon-clear aria-label="Clear icon" title="Clear icon" disabled', $html);
    }

    public function test_the_old_value_is_restored_after_a_validation_error_and_a_bad_icon_is_flagged(): void
    {
        // Another field fails: the chosen icon comes back.
        $html = $this->followingRedirects()->from(route('admin.categories.create'))
            ->actingAs($this->admin ??= Admin::factory()->create(), 'admin')
            ->post(route('admin.categories.store'), ['name' => '', 'icon' => 'fas fa-tv'])
            ->getContent();

        $this->assertStringContainsString('name="icon" value="fas fa-tv"', $html);
        $this->assertMatchesRegularExpression('#<i data-icon-preview class="fas fa-tv"></i>#', $html);

        // The icon itself is invalid: the message appears inside the picker.
        $html = $this->followingRedirects()->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['name' => 'Fine', 'icon' => 'fal fa-tshirt'])
            ->getContent();

        $this->assertStringContainsString('The selected icon is not available. Please choose one from the icon picker.', $html);
        $this->assertStringContainsString('is-invalid', $html);
    }

    public function test_the_component_is_reusable_under_another_field_name(): void
    {
        $html = Blade::render('<x-admin.icon-picker name="badge" label="Badge icon" :value="$value" />', ['value' => 'far fa-star']);

        $this->assertStringContainsString('name="badge" value="far fa-star"', $html);
        $this->assertStringContainsString('for="badge-display"', $html);
        $this->assertStringContainsString('Badge icon', $html);
    }

    /* =====================  Table, storefront  ===================== */

    public function test_the_category_list_shows_the_icon_itself_not_its_class_name(): void
    {
        Category::factory()->create(['name' => 'With Icon', 'icon' => 'fas fa-tv', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'No Icon', 'icon' => null, 'sort_order' => 2]);

        $rows = collect($this->fetch()->assertOk()->json('data'))->keyBy('name');

        $this->assertStringContainsString('<i class="fas fa-tv fa-lg"', $rows['With Icon']['visual']);
        $this->assertSame('', trim(strip_tags($rows['With Icon']['visual'])), 'no class text in the cell');
        $this->assertStringNotContainsString('fas fa-tv</', $rows['With Icon']['visual']);
        $this->assertStringContainsString('—', html_entity_decode(strip_tags($rows['No Icon']['visual'])));
        $this->assertStringNotContainsString('<i ', $rows['No Icon']['visual']);

        $this->admin()->get(route('admin.categories.index'))
            ->assertSee('admin-assets/modules/fontawesome-5.15.1/css/all.min.css', false)
            ->assertDontSee('frontend-assets', false);
    }

    public function test_a_picked_icon_reaches_the_storefront_menu(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => 'Picked Cat', 'icon' => 'fas fa-gamepad', 'is_active' => 1, 'show_in_menu' => 1]);

        $menu = $this->get('/')->getContent();

        $this->assertStringContainsString('<i class="fas fa-gamepad"></i> Picked Cat', $menu);
    }

    public function test_a_category_without_an_icon_gets_the_default_menu_icon(): void
    {
        Category::factory()->create(['name' => 'Iconless', 'icon' => null]);

        $this->assertStringContainsString('<i class="fas fa-th-large"></i> Iconless', $this->get('/')->getContent());
    }

    /* =====================  Build command and migration  ===================== */

    public function test_the_build_command_converts_metadata_into_the_local_json(): void
    {
        $output = sys_get_temp_dir().'/icons-test-'.uniqid().'.json';

        $this->artisan('icons:build-fontawesome', [
            '--source' => base_path('tests/Fixtures/icons-sample.yml'),
            '--output' => $output,
            '--fa-version' => '5.15.1',
        ])->assertSuccessful();

        $built = json_decode(file_get_contents($output), true);
        unlink($output);

        $this->assertSame('5.15.1', $built['version']);
        $this->assertSame(['fab fa-apple', 'fas fa-star', 'far fa-star', 'fas fa-tv'], array_column($built['icons'], 'c'));
        $this->assertSame('Television', collect($built['icons'])->firstWhere('c', 'fas fa-tv')['l']);
        $this->assertSame('computer display monitor', collect($built['icons'])->firstWhere('c', 'fas fa-tv')['s']);
    }

    public function test_the_build_command_fails_cleanly_for_a_missing_source(): void
    {
        $this->artisan('icons:build-fontawesome', ['--source' => base_path('tests/Fixtures/nope.yml'), '--output' => sys_get_temp_dir().'/never.json'])
            ->assertFailed();
    }

    public function test_the_migration_maps_old_hand_typed_icons_to_free_ones(): void
    {
        $legacy = [
            'fal fa-tshirt' => 'fas fa-tshirt',
            'fas fa-chair-office' => 'fas fa-chair',
            'fal fa-mobile' => 'fas fa-mobile-alt',
            'far fa-camera' => 'fas fa-camera',
            'fas fa-home-lg-alt' => 'fas fa-home',
            'fa-solid fa-tv' => 'fas fa-tv',
            'fas fa-tv' => 'fas fa-tv',
            'fas fa-does-not-exist' => null,
        ];

        $ids = [];
        foreach (array_keys($legacy) as $icon) {
            $ids[$icon] = Category::factory()->create(['icon' => $icon])->id;
        }
        $iconless = Category::factory()->create(['icon' => null]);

        (include database_path('migrations/2026_10_14_000001_normalize_category_icons.php'))->up();

        foreach ($legacy as $old => $expected) {
            $this->assertSame($expected, Category::find($ids[$old])->icon, $old);
        }

        $this->assertNull($iconless->fresh()->icon);
    }

    public function test_every_seeded_category_icon_is_valid(): void
    {
        $this->seed(CategorySeeder::class);

        $service = app(FontAwesomeIcons::class);

        foreach (Category::pluck('icon') as $icon) {
            $this->assertTrue($service->exists($icon), (string) $icon);
        }
    }
}
