<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\CategoryMenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
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
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function params(array $names, array $extra = []): array
    {
        return array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'columns' => array_map(fn (string $name) => [
                'data' => $name,
                'name' => $name,
                'searchable' => in_array($name, ['DT_RowIndex', 'visual', 'action'], true) ? 'false' : 'true',
                'orderable' => in_array($name, ['DT_RowIndex', 'visual', 'action'], true) ? 'false' : 'true',
            ], $names),
        ], $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function fetch(string $route, array $names, array $extra = []): TestResponse
    {
        // yajra keeps its request wrapper as a singleton; a real request boots a fresh app,
        // but several calls inside one test would otherwise replay the first call's parameters.
        $this->app->forgetInstance('datatables.request');

        return $this->admin()->getJson(route($route, $this->params($names, $extra)));
    }

    private function categoryColumns(): array
    {
        return ['DT_RowIndex', 'visual', 'name', 'sub_categories_count', 'sort_order', 'is_active', 'action'];
    }

    private function subColumns(): array
    {
        return ['DT_RowIndex', 'name', 'category', 'child_categories_count', 'sort_order', 'is_active', 'action'];
    }

    private function childColumns(): array
    {
        return ['DT_RowIndex', 'name', 'category', 'sub_category', 'sort_order', 'is_active', 'action'];
    }

    /**
     * @return array<int, string>
     */
    private function names(TestResponse $response): array
    {
        return collect($response->json('data'))->pluck('name')->map(fn ($n) => html_entity_decode($n))->all();
    }

    /* =====================  Access  ===================== */

    public function test_guests_are_blocked_from_every_category_route(): void
    {
        $category = Category::factory()->create();
        $sub = SubCategory::factory()->create(['category_id' => $category->id]);
        $child = ChildCategory::factory()->create(['sub_category_id' => $sub->id]);

        foreach ([
            route('admin.categories.index'), route('admin.categories.create'), route('admin.categories.edit', $category),
            route('admin.sub-categories.index'), route('admin.sub-categories.create'), route('admin.sub-categories.edit', $sub),
            route('admin.child-categories.index'), route('admin.child-categories.create'), route('admin.child-categories.edit', $child),
        ] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }

        $this->post(route('admin.categories.store'), [])->assertRedirect(route('admin.login'));
        $this->put(route('admin.sub-categories.update', $sub), [])->assertRedirect(route('admin.login'));

        foreach ([
            ['getJson', route('admin.categories.data')],
            ['getJson', route('admin.sub-categories.data')],
            ['getJson', route('admin.child-categories.data')],
            ['getJson', route('admin.categories.sub-categories', $category)],
            ['patchJson', route('admin.categories.status', $category)],
            ['patchJson', route('admin.sub-categories.status', $sub)],
            ['patchJson', route('admin.child-categories.status', $child)],
            ['deleteJson', route('admin.categories.destroy', $category)],
            ['deleteJson', route('admin.child-categories.destroy', $child)],
        ] as [$method, $url]) {
            $this->{$method}($url)->assertUnauthorized();
        }

        $this->assertTrue($category->fresh()->is_active);
        $this->assertNull($child->fresh()->deleted_at);
    }

    public function test_web_guard_users_are_blocked(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->create(), 'web');

        $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
        $this->getJson(route('admin.categories.sub-categories', $category))->assertUnauthorized();
        $this->deleteJson(route('admin.categories.destroy', $category))->assertUnauthorized();
        $this->assertNull($category->fresh()->deleted_at);
    }

    /* =====================  Pages  ===================== */

    public function test_list_pages_have_a_table_and_no_filter_section(): void
    {
        foreach ([
            ['admin.categories.index', 'categories-table'],
            ['admin.sub-categories.index', 'sub-categories-table'],
            ['admin.child-categories.index', 'child-categories-table'],
        ] as [$route, $tableId]) {
            $html = $this->admin()->get(route($route))->assertOk()->assertSee('id="'.$tableId.'"', false)->getContent();

            foreach (['filter-status', 'filter-date', 'filter-reset', 'Filters', 'vendor-filters'] as $marker) {
                $this->assertStringNotContainsString($marker, $html, "{$route}: {$marker}");
            }

            $this->assertStringNotContainsString('frontend-assets', $html);
            $this->assertStringNotContainsString('style="', substr($html, strpos($html, 'main-content')));
        }
    }

    public function test_the_table_shell_does_not_use_attributes_that_datatables_reads_as_options(): void
    {
        // DataTables treats data-* attributes on the table as init options: a data-columns attribute
        // would silently replace the column list built by crud-table.js (the Actions column vanished).
        foreach (['admin.categories.index', 'admin.sub-categories.index', 'admin.child-categories.index'] as $route) {
            $html = $this->admin()->get(route($route))->assertOk()->getContent();

            $this->assertStringNotContainsString('data-columns=', $html, $route);
            $this->assertStringContainsString('data-table-columns=', $html, $route);

            // Header cells = configured columns + the appended Actions column.
            preg_match('/data-table-columns="([^"]+)"/', $html, $match);
            $configured = count(json_decode(html_entity_decode($match[1]), true));
            $this->assertSame($configured + 1, preg_match_all('/<th[\s>]/', $html), $route);
        }
    }

    public function test_sidebar_has_a_categories_group_with_three_items(): void
    {
        $this->admin()->get(route('admin.categories.index'))
            ->assertSee('Categories')
            ->assertSee('href="'.route('admin.categories.index').'"', false)
            ->assertSee('href="'.route('admin.sub-categories.index').'"', false)
            ->assertSee('href="'.route('admin.child-categories.index').'"', false)
            ->assertSeeInOrder(['class="dropdown active"', route('admin.categories.index')], false);
    }

    public function test_create_pages_render(): void
    {
        $this->admin()->get(route('admin.categories.create'))->assertOk()
            ->assertSee('name="icon"', false)->assertSee('name="show_in_menu"', false)->assertSee('enctype="multipart/form-data"', false);

        $this->admin()->get(route('admin.sub-categories.create'))->assertOk()->assertSee('name="category_id"', false);

        $this->admin()->get(route('admin.child-categories.create'))->assertOk()
            ->assertSee('data-dependent-source', false)
            ->assertSee('data-dependent-target="#sub_category_id"', false)
            ->assertSee('name="sub_category_id"', false);
    }

    public function test_child_edit_page_carries_the_selected_sub_category_for_the_dependent_dropdown(): void
    {
        $child = ChildCategory::factory()->create();

        $this->admin()->get(route('admin.child-categories.edit', $child))
            ->assertOk()
            ->assertSee('data-selected="'.$child->sub_category_id.'"', false)
            ->assertSee('<option value="'.$child->category_id.'" selected', false);
    }

    /* =====================  Category CRUD  ===================== */

    public function test_admin_can_create_a_category(): void
    {
        $this->admin()->post(route('admin.categories.store'), [
            'name' => 'Home & Garden',
            'icon' => 'fas fa-home',
            'image' => UploadedFile::fake()->image('cover.jpg'),
            'sort_order' => 4,
            'is_active' => 1,
            'show_in_menu' => 1,
        ])->assertRedirect(route('admin.categories.index'))->assertSessionHas('success');

        $category = Category::firstOrFail();

        $this->assertSame('Home & Garden', $category->name);
        $this->assertSame('home-garden', $category->slug);
        $this->assertSame('fas fa-home', $category->icon);
        $this->assertSame(4, $category->sort_order);
        $this->assertTrue($category->is_active);
        $this->assertTrue($category->show_in_menu);
        $this->assertStringStartsWith('categories/', $category->image);
        $this->assertStringNotContainsString('cover', basename($category->image));
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_category_defaults_and_off_switches(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => 'Plain', 'is_active' => 0, 'show_in_menu' => 0])
            ->assertSessionHasNoErrors();

        $category = Category::firstOrFail();

        $this->assertSame(0, $category->sort_order);
        $this->assertNull($category->icon);
        $this->assertNull($category->image);
        $this->assertFalse($category->is_active);
        $this->assertFalse($category->show_in_menu);
        $this->assertSame('fas fa-th-large', $category->icon_class);
    }

    public function test_category_validation(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->admin()->post(route('admin.categories.store'), ['name' => str_repeat('a', 101)])->assertSessionHasErrors('name');
        $this->admin()->post(route('admin.categories.store'), ['name' => 'X', 'sort_order' => -1])->assertSessionHasErrors('sort_order');

        // Icon rules are covered in IconPickerTest.
    }

    public function test_category_image_rules_exclude_svg_and_large_files(): void
    {
        foreach ([
            UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->image('huge.jpg')->size(1500),
        ] as $file) {
            $this->admin()->post(route('admin.categories.store'), ['name' => 'Img', 'image' => $file])->assertSessionHasErrors('image');
        }

        $this->assertSame(0, Category::count());
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->admin()->post(route('admin.categories.store'), ['name' => 'Img', 'image' => UploadedFile::fake()->image('ok.webp')])
            ->assertSessionHasNoErrors();
    }

    public function test_category_name_is_unique_but_a_deleted_name_can_be_reused(): void
    {
        $existing = Category::factory()->create(['name' => 'Fashion']);

        $this->admin()->post(route('admin.categories.store'), ['name' => 'Fashion'])->assertSessionHasErrors('name');

        // Editing the same category keeps its own name.
        $this->admin()->put(route('admin.categories.update', $existing), ['name' => 'Fashion', 'sort_order' => 2])
            ->assertSessionHasNoErrors();

        $existing->delete();

        $this->admin()->post(route('admin.categories.store'), ['name' => 'Fashion'])->assertSessionHasNoErrors();

        // The deleted row still owns its slug, so the new one gets a suffix instead of a database error.
        $this->assertSame(['fashion', 'fashion-2'], Category::withTrashed()->orderBy('id')->pluck('slug')->all());
    }

    public function test_slugs_are_unique_even_when_names_differ(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => 'T-Shirts']);
        $this->admin()->post(route('admin.categories.store'), ['name' => 'T Shirts']);

        $this->assertSame(['t-shirts', 't-shirts-2'], Category::orderBy('id')->pluck('slug')->all());
    }

    public function test_updating_a_category_regenerates_the_slug_and_swaps_the_image(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => 'Old Name', 'image' => UploadedFile::fake()->image('a.jpg')]);
        $category = Category::firstOrFail();
        $oldImage = $category->image;

        $this->admin()->put(route('admin.categories.update', $category), [
            'name' => 'New Name',
            'icon' => 'fas fa-tv',
            'image' => UploadedFile::fake()->image('b.png'),
            'is_active' => 1,
            'show_in_menu' => 1,
        ])->assertSessionHas('success');

        $category->refresh();

        $this->assertSame('new-name', $category->slug);
        $this->assertSame('fas fa-tv', $category->icon);
        $this->assertNotSame($oldImage, $category->image);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_the_slug_is_kept_when_the_name_does_not_change(): void
    {
        $category = Category::factory()->create(['name' => 'Stable']);

        $this->admin()->put(route('admin.categories.update', $category), ['name' => 'Stable', 'sort_order' => 9]);

        $this->assertSame('stable', $category->fresh()->slug);
    }

    public function test_the_current_image_can_be_removed(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => 'Pic', 'image' => UploadedFile::fake()->image('a.jpg')]);
        $category = Category::firstOrFail();
        $image = $category->image;

        $this->admin()->put(route('admin.categories.update', $category), ['name' => 'Pic', 'remove_image' => 1]);

        $this->assertNull($category->fresh()->image);
        Storage::disk('public')->assertMissing($image);
    }

    public function test_an_invalid_replacement_keeps_the_old_image(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => 'Keep', 'image' => UploadedFile::fake()->image('a.jpg')]);
        $category = Category::firstOrFail();

        $this->admin()->put(route('admin.categories.update', $category), [
            'name' => 'Keep', 'image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        Storage::disk('public')->assertExists($category->image);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_category_status_toggle_returns_json(): void
    {
        $category = Category::factory()->create();

        $this->admin()->patchJson(route('admin.categories.status', $category))
            ->assertOk()->assertJson(['is_active' => false, 'message' => 'Category deactivated.']);
        $this->assertFalse($category->fresh()->is_active);

        $this->admin()->patchJson(route('admin.categories.status', $category))
            ->assertOk()->assertJson(['is_active' => true, 'message' => 'Category activated.']);
    }

    public function test_a_category_without_children_can_be_deleted_and_keeps_its_image_until_force_delete(): void
    {
        $this->admin()->post(route('admin.categories.store'), ['name' => 'Gone', 'image' => UploadedFile::fake()->image('a.jpg')]);
        $category = Category::firstOrFail();

        $this->admin()->deleteJson(route('admin.categories.destroy', $category))
            ->assertOk()->assertJson(['message' => 'Category deleted.']);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        Storage::disk('public')->assertExists($category->image);

        $category->forceDelete();

        Storage::disk('public')->assertMissing($category->image);
    }

    public function test_deleting_a_category_with_sub_categories_is_blocked_with_a_clear_message(): void
    {
        $category = Category::factory()->create();
        SubCategory::factory()->count(2)->create(['category_id' => $category->id]);

        $this->admin()->deleteJson(route('admin.categories.destroy', $category))
            ->assertStatus(422)
            ->assertJson(['message' => 'This category still has 2 sub categories. Remove or move them first.']);

        $this->assertNull($category->fresh()->deleted_at);

        // Without AJAX the message comes back as a flash.
        $this->admin()->delete(route('admin.categories.destroy', $category))->assertSessionHas('error');
    }

    public function test_a_single_sub_category_message_is_singular(): void
    {
        $category = Category::factory()->create();
        SubCategory::factory()->create(['category_id' => $category->id]);

        $this->admin()->deleteJson(route('admin.categories.destroy', $category))
            ->assertStatus(422)->assertJson(['message' => 'This category still has 1 sub category. Remove or move them first.']);
    }

    public function test_once_its_sub_categories_are_deleted_the_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();
        $sub = SubCategory::factory()->create(['category_id' => $category->id]);

        $this->admin()->deleteJson(route('admin.sub-categories.destroy', $sub))->assertOk();
        $this->admin()->deleteJson(route('admin.categories.destroy', $category))->assertOk();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    /* =====================  Sub category CRUD  ===================== */

    public function test_admin_can_create_a_sub_category(): void
    {
        $category = Category::factory()->create();

        $this->admin()->post(route('admin.sub-categories.store'), [
            'category_id' => $category->id, 'name' => "Men's", 'sort_order' => 2, 'is_active' => 1,
        ])->assertRedirect(route('admin.sub-categories.index'))->assertSessionHas('success');

        $sub = SubCategory::firstOrFail();

        $this->assertSame($category->id, $sub->category_id);
        $this->assertSame('mens', $sub->slug);
        $this->assertSame(2, $sub->sort_order);
        $this->assertTrue($sub->is_active);
    }

    public function test_sub_category_requires_an_existing_category(): void
    {
        $deleted = Category::factory()->create();
        $deleted->delete();

        $this->admin()->post(route('admin.sub-categories.store'), ['name' => 'X'])->assertSessionHasErrors('category_id');
        $this->admin()->post(route('admin.sub-categories.store'), ['name' => 'X', 'category_id' => 9999])->assertSessionHasErrors('category_id');
        $this->admin()->post(route('admin.sub-categories.store'), ['name' => 'X', 'category_id' => $deleted->id])->assertSessionHasErrors('category_id');
        $this->admin()->post(route('admin.sub-categories.store'), ['category_id' => Category::factory()->create()->id, 'name' => ''])->assertSessionHasErrors('name');

        $this->assertSame(0, SubCategory::count());
    }

    public function test_sub_category_names_are_unique_within_a_category_only(): void
    {
        $one = Category::factory()->create();
        $two = Category::factory()->create();

        $this->admin()->post(route('admin.sub-categories.store'), ['category_id' => $one->id, 'name' => 'Accessories'])->assertSessionHasNoErrors();
        $this->admin()->post(route('admin.sub-categories.store'), ['category_id' => $one->id, 'name' => 'Accessories'])->assertSessionHasErrors('name');

        // The same name (and so the same slug) under a different category is fine.
        $this->admin()->post(route('admin.sub-categories.store'), ['category_id' => $two->id, 'name' => 'Accessories'])->assertSessionHasNoErrors();

        $this->assertSame(['accessories', 'accessories'], SubCategory::orderBy('id')->pluck('slug')->all());
    }

    public function test_editing_a_sub_category_may_keep_its_own_name(): void
    {
        $sub = SubCategory::factory()->create(['name' => 'Keep Me']);

        $this->admin()->put(route('admin.sub-categories.update', $sub), [
            'category_id' => $sub->category_id, 'name' => 'Keep Me', 'sort_order' => 5, 'is_active' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.sub-categories.index'));

        $this->assertSame(5, $sub->fresh()->sort_order);
        $this->assertFalse($sub->fresh()->is_active);
    }

    public function test_moving_a_sub_category_moves_its_children_with_it(): void
    {
        $from = Category::factory()->create();
        $to = Category::factory()->create();
        $sub = SubCategory::factory()->create(['category_id' => $from->id, 'name' => 'Movable']);
        $children = ChildCategory::factory()->count(2)->create(['sub_category_id' => $sub->id]);

        $this->admin()->put(route('admin.sub-categories.update', $sub), ['category_id' => $to->id, 'name' => 'Movable', 'is_active' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame($to->id, $sub->fresh()->category_id);

        foreach ($children as $child) {
            $this->assertSame($to->id, $child->fresh()->category_id);
        }
    }

    public function test_moving_a_sub_category_into_a_category_with_the_same_slug_gets_a_suffix(): void
    {
        $from = Category::factory()->create();
        $to = Category::factory()->create();
        SubCategory::factory()->create(['category_id' => $to->id, 'name' => 'Shoes']);
        $moving = SubCategory::factory()->create(['category_id' => $from->id, 'name' => 'Shoes']);

        $this->admin()->put(route('admin.sub-categories.update', $moving), ['category_id' => $to->id, 'name' => 'Shoes 2', 'is_active' => 1]);

        $this->assertSame('shoes-2', $moving->fresh()->slug);
    }

    public function test_sub_category_status_toggle(): void
    {
        $sub = SubCategory::factory()->create();

        $this->admin()->patchJson(route('admin.sub-categories.status', $sub))->assertOk()->assertJson(['is_active' => false]);
        $this->assertFalse($sub->fresh()->is_active);
    }

    public function test_deleting_a_sub_category_with_child_categories_is_blocked(): void
    {
        $sub = SubCategory::factory()->create();
        ChildCategory::factory()->count(3)->create(['sub_category_id' => $sub->id]);

        $this->admin()->deleteJson(route('admin.sub-categories.destroy', $sub))
            ->assertStatus(422)
            ->assertJson(['message' => 'This sub category still has 3 child categories. Remove or move them first.']);

        $this->assertNull($sub->fresh()->deleted_at);
    }

    public function test_a_sub_category_without_children_can_be_deleted(): void
    {
        $sub = SubCategory::factory()->create();

        $this->admin()->deleteJson(route('admin.sub-categories.destroy', $sub))->assertOk()->assertJson(['message' => 'Sub category deleted.']);

        $this->assertSoftDeleted('sub_categories', ['id' => $sub->id]);
    }

    /* =====================  Child category CRUD  ===================== */

    public function test_admin_can_create_a_child_category(): void
    {
        $sub = SubCategory::factory()->create();

        $this->admin()->post(route('admin.child-categories.store'), [
            'category_id' => $sub->category_id, 'sub_category_id' => $sub->id, 'name' => 'T-Shirts', 'sort_order' => 1, 'is_active' => 1,
        ])->assertRedirect(route('admin.child-categories.index'))->assertSessionHas('success');

        $child = ChildCategory::firstOrFail();

        $this->assertSame($sub->id, $child->sub_category_id);
        $this->assertSame($sub->category_id, $child->category_id);
        $this->assertSame('t-shirts', $child->slug);
    }

    public function test_child_category_is_rejected_when_the_sub_category_belongs_to_another_category(): void
    {
        $sub = SubCategory::factory()->create();
        $otherCategory = Category::factory()->create();

        $this->admin()->post(route('admin.child-categories.store'), [
            'category_id' => $otherCategory->id, 'sub_category_id' => $sub->id, 'name' => 'Mismatch',
        ])->assertSessionHasErrors(['sub_category_id' => 'The selected sub category does not belong to the selected category.']);

        $this->assertSame(0, ChildCategory::count());
    }

    public function test_child_category_requires_valid_parents(): void
    {
        $sub = SubCategory::factory()->create();
        $deletedSub = SubCategory::factory()->create(['category_id' => $sub->category_id]);
        $deletedSub->delete();

        $this->admin()->post(route('admin.child-categories.store'), ['name' => 'X'])->assertSessionHasErrors(['category_id', 'sub_category_id']);
        $this->admin()->post(route('admin.child-categories.store'), ['category_id' => $sub->category_id, 'sub_category_id' => 9999, 'name' => 'X'])
            ->assertSessionHasErrors('sub_category_id');
        $this->admin()->post(route('admin.child-categories.store'), ['category_id' => $sub->category_id, 'sub_category_id' => $deletedSub->id, 'name' => 'X'])
            ->assertSessionHasErrors('sub_category_id');
    }

    public function test_the_model_forces_category_id_to_match_the_sub_category(): void
    {
        $sub = SubCategory::factory()->create();
        $wrong = Category::factory()->create();

        $child = ChildCategory::create(['category_id' => $wrong->id, 'sub_category_id' => $sub->id, 'name' => 'Forced']);

        $this->assertSame($sub->category_id, $child->fresh()->category_id);

        // Re-parenting a child to another sub category follows that sub category's category.
        $otherSub = SubCategory::factory()->create();
        $child->update(['sub_category_id' => $otherSub->id]);

        $this->assertSame($otherSub->category_id, $child->fresh()->category_id);
    }

    public function test_child_names_are_unique_within_a_sub_category_only(): void
    {
        $subA = SubCategory::factory()->create();
        $subB = SubCategory::factory()->create();

        $post = fn (SubCategory $sub, string $name) => $this->admin()->post(route('admin.child-categories.store'), [
            'category_id' => $sub->category_id, 'sub_category_id' => $sub->id, 'name' => $name,
        ]);

        $post($subA, 'Bags')->assertSessionHasNoErrors();
        $post($subA, 'Bags')->assertSessionHasErrors('name');
        $post($subB, 'Bags')->assertSessionHasNoErrors();

        $this->assertSame(2, ChildCategory::count());
    }

    public function test_admin_can_update_a_child_category_and_move_it_to_another_parent(): void
    {
        $child = ChildCategory::factory()->create(['name' => 'Before']);
        $newSub = SubCategory::factory()->create();

        $this->admin()->put(route('admin.child-categories.update', $child), [
            'category_id' => $newSub->category_id, 'sub_category_id' => $newSub->id, 'name' => 'After', 'is_active' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.child-categories.index'));

        $child->refresh();

        $this->assertSame('After', $child->name);
        $this->assertSame('after', $child->slug);
        $this->assertSame($newSub->id, $child->sub_category_id);
        $this->assertSame($newSub->category_id, $child->category_id);
        $this->assertFalse($child->is_active);
    }

    public function test_child_status_toggle_and_delete(): void
    {
        $child = ChildCategory::factory()->create();

        $this->admin()->patchJson(route('admin.child-categories.status', $child))->assertOk()->assertJson(['is_active' => false]);
        $this->admin()->deleteJson(route('admin.child-categories.destroy', $child))->assertOk()->assertJson(['message' => 'Child category deleted.']);

        $this->assertSoftDeleted('child_categories', ['id' => $child->id]);
    }

    /* =====================  Dependent dropdown  ===================== */

    public function test_the_dependent_dropdown_lists_only_that_categorys_active_sub_categories(): void
    {
        $category = Category::factory()->create();
        $other = Category::factory()->create();

        SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Second', 'sort_order' => 2]);
        SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'First', 'sort_order' => 1]);
        SubCategory::factory()->inactive()->create(['category_id' => $category->id, 'name' => 'Inactive']);
        SubCategory::factory()->create(['category_id' => $other->id, 'name' => 'Elsewhere']);
        SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Deleted'])->delete();

        $response = $this->admin()->getJson(route('admin.categories.sub-categories', $category))->assertOk();

        $this->assertSame(['First', 'Second'], collect($response->json())->pluck('name')->all());
        $this->assertSame(['id', 'name'], array_keys($response->json('0')));
    }

    public function test_include_adds_a_currently_selected_inactive_sub_category_only_from_that_category(): void
    {
        $category = Category::factory()->create();
        $inactive = SubCategory::factory()->inactive()->create(['category_id' => $category->id, 'name' => 'Currently Selected']);
        $foreign = SubCategory::factory()->inactive()->create(['name' => 'Foreign Inactive']);
        SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Active One']);

        $names = collect($this->admin()->getJson(route('admin.categories.sub-categories', ['category' => $category, 'include' => $inactive->id]))->json())->pluck('name');

        $this->assertEqualsCanonicalizing(['Active One', 'Currently Selected'], $names->all());

        $names = collect($this->admin()->getJson(route('admin.categories.sub-categories', ['category' => $category, 'include' => $foreign->id]))->json())->pluck('name');

        $this->assertSame(['Active One'], $names->all());
    }

    public function test_the_dependent_dropdown_returns_404_for_a_deleted_category(): void
    {
        $category = Category::factory()->create();
        $category->delete();

        $this->admin()->getJson(route('admin.categories.sub-categories', $category->id))->assertNotFound();
    }

    /* =====================  DataTables  ===================== */

    public function test_category_table_json_has_counts_and_no_n_plus_one(): void
    {
        $category = Category::factory()->create(['name' => 'Counted', 'sort_order' => 1, 'icon' => 'fas fa-tv']);
        SubCategory::factory()->count(3)->create(['category_id' => $category->id]);
        Category::factory()->count(8)->create(['sort_order' => 5]);

        $response = $this->fetch('admin.categories.data', $this->categoryColumns())
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
            ->assertJsonPath('recordsTotal', 9)
            ->assertJsonPath('data.0.name', 'Counted')
            ->assertJsonPath('data.0.sub_categories_count', 3)
            ->assertJsonPath('data.0.sort_order', 1);

        $row = $response->json('data.0');

        $this->assertStringContainsString('fa-tv', $row['visual']);
        $this->assertStringContainsString('custom-switch-input', $row['is_active']);
        $this->assertStringContainsString(route('admin.categories.status', $category), $row['is_active']);
        $this->assertStringContainsString(route('admin.categories.edit', $category), $row['action']);
        $this->assertStringContainsString('data-noun="category"', $row['action']);

        DB::enableQueryLog();
        $this->fetch('admin.categories.data', $this->categoryColumns(), ['length' => 25])->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(10, $queries);
    }

    public function test_category_table_search_sort_and_page_length(): void
    {
        Category::factory()->create(['name' => 'Winter', 'sort_order' => 30]);
        $summer = Category::factory()->create(['name' => 'Summer', 'sort_order' => 10]);
        Category::factory()->create(['name' => 'Autumn', 'sort_order' => 20]);
        SubCategory::factory()->count(2)->create(['category_id' => $summer->id]);

        $this->assertSame(['Summer', 'Autumn', 'Winter'], $this->names($this->fetch('admin.categories.data', $this->categoryColumns())));
        $this->assertSame(['Autumn'], $this->names($this->fetch('admin.categories.data', $this->categoryColumns(), ['search' => ['value' => 'autu']])));
        $this->assertSame(['Autumn', 'Summer', 'Winter'], $this->names($this->fetch('admin.categories.data', $this->categoryColumns(), ['order' => [['column' => 2, 'dir' => 'asc']]])));
        $this->assertSame('Summer', $this->names($this->fetch('admin.categories.data', $this->categoryColumns(), ['order' => [['column' => 3, 'dir' => 'desc']]]))[0]);

        $this->fetch('admin.categories.data', $this->categoryColumns(), ['length' => 7])->assertStatus(422)->assertJsonValidationErrors('length');
    }

    public function test_sub_category_table_shows_parent_and_child_count_and_searches_parent_names(): void
    {
        $fashion = Category::factory()->create(['name' => 'Fashion']);
        $tech = Category::factory()->create(['name' => 'Technology']);
        $mens = SubCategory::factory()->create(['category_id' => $fashion->id, 'name' => "Men's", 'sort_order' => 1]);
        SubCategory::factory()->create(['category_id' => $tech->id, 'name' => 'Phones', 'sort_order' => 2]);
        ChildCategory::factory()->count(2)->create(['sub_category_id' => $mens->id]);

        $response = $this->fetch('admin.sub-categories.data', $this->subColumns())->assertOk()->assertJsonPath('recordsTotal', 2);

        $this->assertSame('Fashion', $response->json('data.0.category'));
        $this->assertSame(2, $response->json('data.0.child_categories_count'));
        $this->assertSame(0, $response->json('data.1.child_categories_count'));

        // Searching the parent's name finds its sub categories.
        $this->assertSame(["Men's"], $this->names($this->fetch('admin.sub-categories.data', $this->subColumns(), ['search' => ['value' => 'fashion']])));
        $this->assertSame(['Phones'], $this->names($this->fetch('admin.sub-categories.data', $this->subColumns(), ['search' => ['value' => 'Technol']])));

        $this->assertSame(['Phones', "Men's"], $this->names($this->fetch('admin.sub-categories.data', $this->subColumns(), ['order' => [['column' => 2, 'dir' => 'desc']]])));
        $this->assertSame("Men's", $this->names($this->fetch('admin.sub-categories.data', $this->subColumns(), ['order' => [['column' => 3, 'dir' => 'desc']]]))[0]);
    }

    public function test_child_category_table_shows_both_parents_and_searches_them(): void
    {
        $sub = SubCategory::factory()->create(['name' => 'Footwear']);
        $other = SubCategory::factory()->create(['name' => 'Gadgets']);
        ChildCategory::factory()->create(['sub_category_id' => $sub->id, 'name' => 'Sneakers']);
        ChildCategory::factory()->create(['sub_category_id' => $other->id, 'name' => 'Chargers']);

        $response = $this->fetch('admin.child-categories.data', $this->childColumns())->assertOk()->assertJsonPath('recordsTotal', 2);

        $first = collect($response->json('data'))->firstWhere('name', 'Sneakers');
        $this->assertSame($sub->category->name, html_entity_decode($first['category']));
        $this->assertSame('Footwear', $first['sub_category']);

        $this->assertSame(['Sneakers'], $this->names($this->fetch('admin.child-categories.data', $this->childColumns(), ['search' => ['value' => 'Footwear']])));
        $this->assertSame(['Chargers'], $this->names($this->fetch('admin.child-categories.data', $this->childColumns(), ['search' => ['value' => 'gadg']])));
        $this->assertSame(['Sneakers'], $this->names($this->fetch('admin.child-categories.data', $this->childColumns(), ['search' => ['value' => $sub->category->name]])));
    }

    public function test_tables_include_inactive_rows_because_there_are_no_filters(): void
    {
        Category::factory()->create(['name' => 'On']);
        Category::factory()->inactive()->create(['name' => 'Off']);

        $this->assertEqualsCanonicalizing(['On', 'Off'], $this->names($this->fetch('admin.categories.data', $this->categoryColumns())));
    }

    /* =====================  Menu cache  ===================== */

    public function test_the_menu_cache_is_cleared_by_every_change_at_every_level(): void
    {
        $stale = fn () => Cache::put(CategoryMenuService::CACHE_KEY, 'stale');

        // Category
        $stale();
        $this->admin()->post(route('admin.categories.store'), ['name' => 'Cache Cat', 'is_active' => 1, 'show_in_menu' => 1]);
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'category create');
        $category = Category::firstOrFail();

        $stale();
        $this->admin()->put(route('admin.categories.update', $category), ['name' => 'Cache Cat 2']);
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'category update');

        $stale();
        $this->admin()->patchJson(route('admin.categories.status', $category));
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'category toggle');

        // Sub category
        $stale();
        $this->admin()->post(route('admin.sub-categories.store'), ['category_id' => $category->id, 'name' => 'Cache Sub', 'is_active' => 1]);
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'sub create');
        $sub = SubCategory::firstOrFail();

        $stale();
        $this->admin()->put(route('admin.sub-categories.update', $sub), ['category_id' => $category->id, 'name' => 'Cache Sub 2']);
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'sub update');

        $stale();
        $this->admin()->patchJson(route('admin.sub-categories.status', $sub));
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'sub toggle');

        // Child category
        $stale();
        $this->admin()->post(route('admin.child-categories.store'), ['category_id' => $category->id, 'sub_category_id' => $sub->id, 'name' => 'Cache Child', 'is_active' => 1]);
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'child create');
        $child = ChildCategory::firstOrFail();

        $stale();
        $this->admin()->put(route('admin.child-categories.update', $child), ['category_id' => $category->id, 'sub_category_id' => $sub->id, 'name' => 'Cache Child 2']);
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'child update');

        $stale();
        $this->admin()->patchJson(route('admin.child-categories.status', $child));
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'child toggle');

        // Deletes (children first, because parents with children cannot be deleted)
        $stale();
        $this->admin()->deleteJson(route('admin.child-categories.destroy', $child));
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'child delete');

        $stale();
        $this->admin()->deleteJson(route('admin.sub-categories.destroy', $sub));
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'sub delete');

        $stale();
        $this->admin()->deleteJson(route('admin.categories.destroy', $category));
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'category delete');

        $stale();
        $category->restore();
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'restore');

        $stale();
        $category->forceDelete();
        $this->assertFalse(Cache::has(CategoryMenuService::CACHE_KEY), 'force delete');
    }
}
