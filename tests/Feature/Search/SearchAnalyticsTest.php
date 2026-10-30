<?php

namespace Tests\Feature\Search;

use App\Models\Admin;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /* ---------- Logging ---------- */

    public function test_submitted_searches_are_logged_normalized_with_the_result_count(): void
    {
        Product::factory()->count(2)->create(['short_description' => 'Soft cotton towel']);
        $user = User::factory()->create();

        $this->get(route('shop.index', ['search' => '  Cotton   TOWEL ']))->assertOk();
        $this->actingAs($user, 'web')->get(route('shop.index', ['search' => 'unicorn saddle']))->assertOk();

        $this->assertSame(2, SearchLog::count());
        $this->assertDatabaseHas('search_logs', ['term' => 'cotton towel', 'results_count' => 2, 'user_id' => null]);
        $this->assertDatabaseHas('search_logs', ['term' => 'unicorn saddle', 'results_count' => 0, 'user_id' => $user->id]);
    }

    public function test_suggestions_paging_sorting_and_empty_searches_are_not_logged(): void
    {
        Product::factory()->count(14)->create(['short_description' => 'Canvas tote']);

        $this->getJson(route('frontend.search.suggestions', ['q' => 'canvas']))->assertOk();
        $this->get(route('shop.index', ['search' => 'canvas', 'page' => 2]))->assertOk();
        $this->get(route('shop.index', ['search' => 'canvas', 'sort' => 'price_asc']))->assertOk();
        $this->get(route('shop.index', ['search' => '   ']))->assertOk();
        $this->get(route('shop.index'))->assertOk();

        $this->assertSame(0, SearchLog::count());
    }

    /* ---------- Admin report ---------- */

    public function test_admin_report_groups_terms_and_flags_terms_without_results(): void
    {
        $admin = Admin::factory()->create();
        SearchLog::factory()->count(3)->create(['term' => 'lamp', 'results_count' => 4]);
        SearchLog::factory()->create(['term' => 'lamp', 'results_count' => 0, 'created_at' => now()->addMinute()]);
        SearchLog::factory()->withoutResults()->count(2)->create(['term' => 'unicorn saddle']);

        $this->actingAs($admin, 'admin')->get(route('admin.search-terms.index'))
            ->assertOk()
            ->assertSee('search-terms-table')
            ->assertSee('data-actions="false"', false)
            ->assertSee('data-order-dir="desc"', false)
            ->assertDontSee('<th class="text-right">Actions</th>', false);

        $rows = $this->actingAs($admin, 'admin')->getJson(route('admin.search-terms.data', [
            'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''],
            'columns' => [['data' => 'DT_RowIndex'], ['data' => 'term'], ['data' => 'searches'], ['data' => 'last_searched_at'], ['data' => 'average_results']],
            'order' => [['column' => 2, 'dir' => 'desc']],
        ]))->assertOk()->assertJsonPath('recordsTotal', 2)->json('data');

        $this->assertSame(['lamp', 'unicorn saddle'], array_column($rows, 'term'));
        $this->assertSame([4, 2], array_column($rows, 'searches'));
        $this->assertStringContainsString('3.0', $rows[0]['average_results']);
        $this->assertStringNotContainsString('No results', $rows[0]['average_results']);
        $this->assertStringContainsString('<span class="badge badge-danger ml-1">No results</span>', $rows[1]['average_results']);
        $this->assertSame(now()->addMinute()->format('d M Y, H:i'), $rows[0]['last_searched_at']);

        $this->app->forgetInstance('datatables.request');
        $found = $this->actingAs($admin, 'admin')->getJson(route('admin.search-terms.data', [
            'draw' => 2, 'start' => 0, 'length' => 10, 'search' => ['value' => 'unic'],
        ]))->assertOk()->json('data');
        $this->assertSame(['unicorn saddle'], array_column($found, 'term'));
    }

    public function test_terms_are_escaped_in_the_report(): void
    {
        SearchLog::factory()->create(['term' => '<script>x</script>']);

        $rows = $this->actingAs(Admin::factory()->create(), 'admin')
            ->getJson(route('admin.search-terms.data', ['draw' => 1, 'start' => 0, 'length' => 10]))->json('data');

        $this->assertSame('&lt;script&gt;x&lt;/script&gt;', $rows[0]['term']);
    }

    public function test_only_admins_can_see_the_report(): void
    {
        $this->get(route('admin.search-terms.index'))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(), 'web')->getJson(route('admin.search-terms.data'))->assertUnauthorized();
    }

    public function test_sidebar_links_to_the_report(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')->get(route('admin.dashboard'))
            ->assertSee('href="'.route('admin.search-terms.index').'"', false);
    }
}
