<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\SubCategory;
use App\Services\CategoryMenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryMenuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Category, 1: SubCategory, 2: ChildCategory}
     */
    private function chain(string $category = 'Fashion', string $sub = "Men's", string $child = 'Jeans'): array
    {
        $c = Category::factory()->create(['name' => $category, 'icon' => 'fas fa-tshirt']);
        $s = SubCategory::factory()->create(['category_id' => $c->id, 'name' => $sub]);
        $ch = ChildCategory::factory()->create(['sub_category_id' => $s->id, 'name' => $child]);

        return [$c, $s, $ch];
    }

    private function home(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    /**
     * The desktop category list only (the part inside ul.wsus_menu_cat_item).
     */
    private function desktopMenu(string $html): string
    {
        $start = strpos($html, 'wsus_menu_cat_item');
        $end = strpos($html, 'wsus__menu_item', $start);

        return substr($html, $start, $end - $start);
    }

    private function mobileMenu(string $html): string
    {
        $start = strpos($html, 'wsus_mobile_menu_category');
        $end = strpos($html, 'id="pills-profile"', $start);

        return substr($html, $start, $end - $start);
    }

    /* ---------- Desktop rendering ---------- */

    public function test_all_three_levels_render_with_route_links(): void
    {
        [$category, $sub, $child] = $this->chain();

        $menu = $this->desktopMenu($this->home());

        $this->assertStringContainsString('<i class="fas fa-tshirt"></i> Fashion', $menu);
        $this->assertStringContainsString('href="'.route('category.show', $category->slug).'"', $menu);
        $this->assertStringContainsString('ul class="wsus_menu_cat_droapdown"', $menu);
        $this->assertStringContainsString('href="'.route('category.sub.show', [$category->slug, $sub->slug]).'"', $menu);
        $this->assertStringContainsString("Men's", html_entity_decode($menu));
        $this->assertStringContainsString('ul class="wsus__sub_category"', $menu);
        $this->assertStringContainsString('href="'.route('category.child.show', [$category->slug, $sub->slug, $child->slug]).'"', $menu);
        $this->assertStringContainsString('<i class="fas fa-angle-right"></i>', $menu);
    }

    public function test_the_has_children_markers_appear_only_when_a_level_has_children(): void
    {
        Category::factory()->create(['name' => 'Solo', 'icon' => 'fas fa-star']);

        $menu = $this->desktopMenu($this->home());

        // A category without sub categories: no dropdown arrow class, no dropdown list.
        $this->assertDoesNotMatchRegularExpression('#class="wsus__droap_arrow"[^>]*>\s*<i class="fas fa-star"></i> Solo#', $menu);
        $this->assertStringNotContainsString('wsus_menu_cat_droapdown', $menu);
        $this->assertStringNotContainsString('fa-angle-right', $menu);
        $this->assertStringContainsString('Solo', $menu);

        // Add a sub category without children: the dropdown and arrow class appear, the sub arrow does not.
        $category = Category::firstWhere('name', 'Solo');
        SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Leaf']);

        $menu = $this->desktopMenu($this->home());

        $this->assertMatchesRegularExpression('#class="wsus__droap_arrow"[^>]*><i class="fas fa-star"></i> Solo#', $menu);
        $this->assertStringContainsString('wsus_menu_cat_droapdown', $menu);
        $this->assertStringNotContainsString('fa-angle-right', $menu);
        $this->assertStringNotContainsString('wsus__sub_category', $menu);
    }

    public function test_a_category_without_an_icon_uses_the_fallback_icon(): void
    {
        Category::factory()->create(['name' => 'No Icon', 'icon' => null]);

        $this->assertStringContainsString('<i class="fas fa-th-large"></i> No Icon', $this->desktopMenu($this->home()));
    }

    public function test_names_are_escaped(): void
    {
        $category = Category::factory()->create(['name' => '<script>alert(1)</script>']);
        SubCategory::factory()->create(['category_id' => $category->id, 'name' => '<b>bold</b>']);

        $html = $this->home();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<b>bold</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_levels_follow_the_sort_order(): void
    {
        Category::factory()->create(['name' => 'Cat C', 'sort_order' => 3]);
        $a = Category::factory()->create(['name' => 'Cat A', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'Cat B', 'sort_order' => 2]);
        SubCategory::factory()->create(['category_id' => $a->id, 'name' => 'Sub Z', 'sort_order' => 2]);
        SubCategory::factory()->create(['category_id' => $a->id, 'name' => 'Sub Y', 'sort_order' => 1]);

        $menu = $this->desktopMenu($this->home());

        $this->assertLessThan(strpos($menu, 'Cat B'), strpos($menu, 'Cat A'));
        $this->assertLessThan(strpos($menu, 'Cat C'), strpos($menu, 'Cat B'));
        $this->assertLessThan(strpos($menu, 'Sub Z'), strpos($menu, 'Sub Y'));
    }

    public function test_the_static_template_items_are_still_there(): void
    {
        $menu = $this->desktopMenu($this->home());

        $this->assertStringContainsString('hot promotions', $menu);
        $this->assertStringContainsString('View All Categories', $menu);
        $this->assertStringContainsString('href="'.route('shop.index').'"', $menu);
    }

    /* ---------- Visibility rules ---------- */

    public function test_inactive_levels_are_hidden_together_with_everything_below_them(): void
    {
        [$category, $sub, $child] = $this->chain('Visible Cat', 'Visible Sub', 'Visible Child');
        [$offCategory] = $this->chain('Off Cat', 'Under Off Cat', 'Child Under Off Cat');
        [, $offSub] = $this->chain('Alpha Parent', 'Hidden Sub', 'Child Under Hidden Sub');
        [, , $offChild] = $this->chain('Beta Parent', 'Beta Sub', 'Hidden Child');
        Category::factory()->hiddenFromMenu()->create(['name' => 'Not In Menu']);

        $offCategory->update(['is_active' => false]);
        $offSub->update(['is_active' => false]);
        $offChild->update(['is_active' => false]);

        $menu = $this->desktopMenu($this->home());

        foreach (['Visible Cat', 'Visible Sub', 'Visible Child', 'Alpha Parent', 'Beta Parent', 'Beta Sub'] as $shown) {
            $this->assertStringContainsString($shown, $menu, $shown);
        }

        foreach (['Off Cat', 'Under Off Cat', 'Child Under Off Cat', 'Hidden Sub', 'Child Under Hidden Sub', 'Hidden Child', 'Not In Menu'] as $gone) {
            $this->assertStringNotContainsString($gone, $menu, $gone);
        }

        // Re-activating brings the whole subtree back.
        $offCategory->update(['is_active' => true]);
        $this->assertStringContainsString('Child Under Off Cat', $this->desktopMenu($this->home()));
    }

    public function test_deleted_levels_are_hidden(): void
    {
        [, $sub, $child] = $this->chain('Parent', 'Doomed Sub', 'Doomed Child');

        $child->delete();
        $menu = $this->desktopMenu($this->home());
        $this->assertStringNotContainsString('Doomed Child', $menu);
        $this->assertStringContainsString('Doomed Sub', $menu);

        $sub->delete();
        $this->assertStringNotContainsString('Doomed Sub', $this->desktopMenu($this->home()));
    }

    /* ---------- Mobile ---------- */

    public function test_the_mobile_menu_renders_accordions_for_levels_with_children(): void
    {
        [$category, $sub, $child] = $this->chain();
        Category::factory()->create(['name' => 'Plain Mobile']);
        SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Leaf Sub']);

        $mobile = $this->mobileMenu($this->home());

        $this->assertStringContainsString('data-bs-target="#mobile-cat-'.$category->id.'"', $mobile);
        $this->assertStringContainsString('id="mobile-cat-'.$category->id.'"', $mobile);
        $this->assertStringContainsString('All Fashion', $mobile);
        $this->assertStringContainsString('data-bs-target="#mobile-sub-'.$sub->id.'"', $mobile);
        $this->assertStringContainsString('id="mobile-sub-'.$sub->id.'"', $mobile);
        $this->assertStringContainsString('href="'.route('category.child.show', [$category->slug, $sub->slug, $child->slug]).'"', $mobile);

        // A sub category without children is a plain link, and a category without subs has no accordion.
        $this->assertStringContainsString('Leaf Sub', $mobile);
        $this->assertSame(1, substr_count($mobile, 'id="mobile-sub-'), 'a collapse exists only for the sub category that has children');
        $this->assertStringContainsString('Plain Mobile', $mobile);
        $this->assertStringNotContainsString('data-bs-target="#mobile-cat-'.Category::firstWhere('name', 'Plain Mobile')->id.'"', $mobile);
    }

    public function test_inactive_levels_are_hidden_on_mobile_too(): void
    {
        [, $offSub] = $this->chain('Mobile Cat', 'Mobile Off Sub', 'Mobile Off Child');
        $offSub->update(['is_active' => false]);

        $mobile = $this->mobileMenu($this->home());

        $this->assertStringContainsString('Mobile Cat', $mobile);
        $this->assertStringNotContainsString('Mobile Off Sub', $mobile);
        $this->assertStringNotContainsString('Mobile Off Child', $mobile);
    }

    /* ---------- Service, cache, composer ---------- */

    public function test_the_tree_is_built_in_one_place_with_only_the_needed_data(): void
    {
        [$category, $sub, $child] = $this->chain();

        $tree = app(CategoryMenuService::class)->tree();

        $this->assertCount(1, $tree);
        $this->assertSame(['id', 'name', 'slug', 'icon', 'subs'], array_keys($tree[0]));
        $this->assertSame(['id', 'name', 'slug', 'children'], array_keys($tree[0]['subs'][0]));
        $this->assertSame(['id', 'name', 'slug'], array_keys($tree[0]['subs'][0]['children'][0]));
        $this->assertSame($child->slug, $tree[0]['subs'][0]['children'][0]['slug']);
    }

    public function test_the_tree_is_eager_loaded_with_a_constant_number_of_queries(): void
    {
        foreach (range(1, 4) as $i) {
            $category = Category::factory()->create();
            foreach (range(1, 3) as $j) {
                $sub = SubCategory::factory()->create(['category_id' => $category->id]);
                ChildCategory::factory()->count(2)->create(['sub_category_id' => $sub->id]);
            }
        }

        Cache::forget(CategoryMenuService::CACHE_KEY);

        DB::enableQueryLog();
        app(CategoryMenuService::class)->tree();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(3, $queries, 'categories + sub categories + child categories');
    }

    public function test_the_menu_is_cached_and_not_queried_again(): void
    {
        $this->chain('Cached Cat');

        $this->get('/')->assertSee('Cached Cat');
        $this->assertTrue(Cache::has(CategoryMenuService::CACHE_KEY));

        DB::enableQueryLog();
        $this->get('/')->assertSee('Cached Cat');
        $categoryQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'categories'))->count();
        DB::disableQueryLog();

        $this->assertSame(0, $categoryQueries);
    }

    public function test_changes_show_up_immediately_because_observers_clear_the_cache(): void
    {
        [$category, $sub, $child] = $this->chain('Before Cat', 'Before Sub', 'Before Child');

        $this->assertStringContainsString('Before Child', $this->home());

        $child->update(['name' => 'After Child']);
        $html = $this->home();
        $this->assertStringContainsString('After Child', $html);
        $this->assertStringNotContainsString('Before Child', $html);

        $sub->update(['is_active' => false]);
        $this->assertStringNotContainsString('After Child', $this->home());

        $sub->update(['is_active' => true]);
        $category->update(['show_in_menu' => false]);
        $this->assertStringNotContainsString('Before Cat', $this->desktopMenu($this->home()));
    }

    public function test_the_menu_view_gets_its_data_from_the_composer_not_from_blade_queries(): void
    {
        $menuFiles = [
            'frontend/layouts/menu.blade.php',
            'frontend/layouts/menu/categories-desktop.blade.php',
            'frontend/layouts/menu/categories-mobile.blade.php',
        ];

        foreach ($menuFiles as $file) {
            $source = file_get_contents(resource_path('views/'.$file));

            $this->assertDoesNotMatchRegularExpression('/(Category|SubCategory|ChildCategory)::|DB::/', $source, $file);
        }
    }

    /* ---------- Frontend routes ---------- */

    public function test_category_pages_load_for_an_active_chain(): void
    {
        [$category, $sub, $child] = $this->chain();

        $this->get(route('category.show', $category->slug))->assertOk()->assertSee('Fashion')->assertSee("Men's")->assertSee('coming soon');
        $this->get(route('category.sub.show', [$category->slug, $sub->slug]))->assertOk()->assertSee('Jeans');
        $this->get(route('category.child.show', [$category->slug, $sub->slug, $child->slug]))->assertOk()->assertSee('Jeans');
        $this->get(route('shop.index'))->assertOk()->assertSee('Fashion');
    }

    public function test_category_pages_are_404_for_inactive_deleted_or_mismatched_levels(): void
    {
        [$category, $sub, $child] = $this->chain();
        [$otherCategory, $otherSub] = $this->chain('Other', 'Other Sub', 'Other Child');

        // Wrong parent in the URL.
        $this->get(route('category.sub.show', [$otherCategory->slug, $sub->slug]))->assertNotFound();
        $this->get(route('category.child.show', [$category->slug, $otherSub->slug, $child->slug]))->assertNotFound();
        $this->get(route('category.child.show', [$category->slug, $sub->slug, 'no-such-child']))->assertNotFound();
        $this->get('/category/no-such-category')->assertNotFound();

        // An inactive level hides everything beneath it.
        $sub->update(['is_active' => false]);
        $this->get(route('category.show', $category->slug))->assertOk();
        $this->get(route('category.sub.show', [$category->slug, $sub->slug]))->assertNotFound();
        $this->get(route('category.child.show', [$category->slug, $sub->slug, $child->slug]))->assertNotFound();

        $sub->update(['is_active' => true]);
        $category->update(['is_active' => false]);
        $this->get(route('category.show', $category->slug))->assertNotFound();
        $this->get(route('category.sub.show', [$category->slug, $sub->slug]))->assertNotFound();
        $this->get(route('category.child.show', [$category->slug, $sub->slug, $child->slug]))->assertNotFound();

        // A category hidden from the menu is still reachable by URL (only the menu hides it).
        $category->update(['is_active' => true, 'show_in_menu' => false]);
        $this->get(route('category.show', $category->slug))->assertOk();
    }

    public function test_category_pages_do_not_load_admin_assets(): void
    {
        [$category] = $this->chain();

        $this->get(route('category.show', $category->slug))->assertDontSee('admin-assets');
    }
}
