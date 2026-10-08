<?php

namespace Tests\Feature\Admin;

use App\Enums\VendorAction;
use App\Enums\VendorStatus;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VendorDataTableTest extends TestCase
{
    use RefreshDatabase;

    private ?Admin $admin = null;

    private function admin(): static
    {
        return $this->actingAs($this->admin ??= Admin::factory()->create(), 'admin');
    }

    /**
     * Query string the DataTables client sends, plus the page filters.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function params(array $extra = []): array
    {
        $columns = array_map(fn (string $name) => [
            'data' => $name,
            'name' => $name,
            'searchable' => in_array($name, ['DT_RowIndex', 'action'], true) ? 'false' : 'true',
            'orderable' => in_array($name, ['DT_RowIndex', 'action'], true) ? 'false' : 'true',
        ], ['DT_RowIndex', 'shop', 'owner', 'email', 'phone', 'status', 'created_at', 'action']);

        return array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'columns' => $columns,
            'order' => [['column' => 6, 'dir' => 'desc']],
        ], $extra);
    }

    private function fetch(array $extra = []): TestResponse
    {
        // yajra keeps its request wrapper as a singleton; a real request boots a fresh app,
        // but several calls inside one test would otherwise replay the first call's parameters.
        $this->app->forgetInstance('datatables.request');

        return $this->admin()->getJson(route('admin.vendors.data', $this->params($extra)));
    }

    /**
     * @return array<int, string>
     */
    private function shopNames(TestResponse $response): array
    {
        return collect($response->json('data'))
            ->map(fn (array $row) => trim(strip_tags($row['shop'])))
            ->all();
    }

    public function test_endpoint_is_admin_only(): void
    {
        $this->getJson(route('admin.vendors.data'))->assertUnauthorized();

        $this->actingAs(User::factory()->create(), 'web')
            ->getJson(route('admin.vendors.data'))
            ->assertUnauthorized();
    }

    public function test_returns_datatables_json_with_expected_columns(): void
    {
        $vendor = Vendor::factory()->create(['shop_name' => 'Json Shop', 'phone' => '555-0001']);

        $this->fetch()
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data', 'counts' => ['all', 'pending', 'approved', 'rejected', 'suspended']])
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.DT_RowIndex', 1)
            // Cells are HTML-escaped by yajra (DataTables renders them as HTML).
            ->assertJsonPath('data.0.owner', e($vendor->user->name))
            ->assertJsonPath('data.0.email', e($vendor->user->email))
            ->assertJsonPath('data.0.phone', '555-0001')
            ->assertJsonPath('data.0.created_at', $vendor->created_at->format('d M Y'));

        $this->assertStringContainsString('Json Shop', $this->fetch()->json('data.0.shop'));
        $this->assertStringContainsString('badge-warning', $this->fetch()->json('data.0.status'));
    }

    public function test_row_actions_follow_the_vendor_status(): void
    {
        Vendor::factory()->create(['shop_name' => 'Pending One']);

        $actions = $this->fetch()->json('data.0.action');

        $this->assertStringContainsString('data-vendor-action="approve"', $actions);
        $this->assertStringContainsString('data-vendor-action="reject"', $actions);
        $this->assertStringNotContainsString('data-vendor-action="suspend"', $actions);
        $this->assertStringNotContainsString('data-vendor-action="reactivate"', $actions);
        $this->assertStringContainsString('fa-eye', $actions);
    }

    public function test_every_status_exposes_the_right_actions_from_the_enum(): void
    {
        $expected = [
            'pending' => ['approve', 'reject'],
            'approved' => ['reject', 'suspend'],
            'rejected' => ['approve'],
            'suspended' => ['reactivate'],
        ];

        foreach (VendorStatus::cases() as $status) {
            $this->assertSame(
                $expected[$status->value],
                array_map(fn (VendorAction $a) => $a->value, $status->availableActions()),
            );
        }
    }

    public function test_filters_by_status(): void
    {
        Vendor::factory()->create(['shop_name' => 'Pending Palace']);
        Vendor::factory()->approved()->create(['shop_name' => 'Approved Arcade']);
        Vendor::factory()->rejected()->create(['shop_name' => 'Rejected Rack']);

        $response = $this->fetch(['status' => 'approved']);

        $this->assertSame(['Approved Arcade'], $this->shopNames($response));
        $response->assertJsonPath('recordsFiltered', 1);

        // Tab counts ignore the active filter.
        $response->assertJsonPath('counts.all', 3)->assertJsonPath('counts.approved', 1);
    }

    public function test_filters_by_registered_date_range(): void
    {
        Vendor::factory()->create(['shop_name' => 'Old Shop', 'created_at' => '2026-01-05 10:00:00']);
        Vendor::factory()->create(['shop_name' => 'Mid Shop', 'created_at' => '2026-02-15 10:00:00']);
        Vendor::factory()->create(['shop_name' => 'New Shop', 'created_at' => '2026-03-25 23:30:00']);

        $this->assertSame(['Mid Shop'], $this->shopNames($this->fetch(['date_from' => '2026-02-01', 'date_to' => '2026-02-28'])));
        $this->assertSame(['New Shop', 'Mid Shop'], $this->shopNames($this->fetch(['date_from' => '2026-02-01'])));
        // The end date is inclusive for the whole day.
        $this->assertSame(['New Shop'], $this->shopNames($this->fetch(['date_from' => '2026-03-25', 'date_to' => '2026-03-25'])));
        $this->assertSame(['Old Shop'], $this->shopNames($this->fetch(['date_to' => '2026-01-31'])));
    }

    public function test_searches_shop_owner_email_and_phone(): void
    {
        $owner = User::factory()->create(['name' => 'Zelda Owner', 'email' => 'zelda@sample.test', 'role' => 'vendor']);
        Vendor::factory()->create(['user_id' => $owner->id, 'shop_name' => 'Hyrule Goods', 'phone' => '555-7777']);
        Vendor::factory()->create(['shop_name' => 'Other Store', 'phone' => '555-1111']);

        foreach (['Hyrule', 'Zelda', 'zelda@sample', '555-7777'] as $term) {
            $this->assertSame(['Hyrule Goods'], $this->shopNames($this->fetch(['search' => ['value' => $term]])), "term: {$term}");
        }

        $this->assertSame([], $this->shopNames($this->fetch(['search' => ['value' => 'nothing-matches']])));
    }

    public function test_search_and_filters_combine(): void
    {
        Vendor::factory()->create(['shop_name' => 'Alpha Pending']);
        Vendor::factory()->approved()->create(['shop_name' => 'Alpha Approved']);

        $this->assertSame(['Alpha Approved'], $this->shopNames($this->fetch(['status' => 'approved', 'search' => ['value' => 'Alpha']])));
    }

    public function test_sorting_and_default_newest_first(): void
    {
        Vendor::factory()->create(['shop_name' => 'Bravo', 'created_at' => '2026-01-01 10:00:00']);
        Vendor::factory()->create(['shop_name' => 'Alpha', 'created_at' => '2026-02-01 10:00:00']);
        Vendor::factory()->create(['shop_name' => 'Charlie', 'created_at' => '2026-03-01 10:00:00']);

        // The page's default order is created_at desc.
        $this->assertSame(['Charlie', 'Alpha', 'Bravo'], $this->shopNames($this->fetch()));
        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $this->shopNames($this->fetch(['order' => [['column' => 1, 'dir' => 'asc']]])));
        $this->assertSame(['Charlie', 'Bravo', 'Alpha'], $this->shopNames($this->fetch(['order' => [['column' => 1, 'dir' => 'desc']]])));
    }

    public function test_sorting_by_owner_and_email_uses_the_related_user(): void
    {
        $a = User::factory()->create(['name' => 'Aaron', 'email' => 'z@x.test', 'role' => 'vendor']);
        $b = User::factory()->create(['name' => 'Zed', 'email' => 'a@x.test', 'role' => 'vendor']);
        Vendor::factory()->create(['user_id' => $a->id, 'shop_name' => 'Owned By Aaron']);
        Vendor::factory()->create(['user_id' => $b->id, 'shop_name' => 'Owned By Zed']);

        $this->assertSame(['Owned By Aaron', 'Owned By Zed'], $this->shopNames($this->fetch(['order' => [['column' => 2, 'dir' => 'asc']]])));
        $this->assertSame(['Owned By Zed', 'Owned By Aaron'], $this->shopNames($this->fetch(['order' => [['column' => 3, 'dir' => 'asc']]])));
    }

    public function test_paginates_with_the_requested_page_length(): void
    {
        Vendor::factory()->count(12)->create();

        $first = $this->fetch(['length' => 10, 'start' => 0]);
        $second = $this->fetch(['length' => 10, 'start' => 10]);

        $first->assertJsonPath('recordsTotal', 12);
        $this->assertCount(10, $first->json('data'));
        $this->assertCount(2, $second->json('data'));
        $this->assertCount(12, $this->fetch(['length' => 25])->json('data'));
    }

    public function test_relations_are_eager_loaded(): void
    {
        Vendor::factory()->count(8)->create();

        DB::enableQueryLog();
        $this->fetch(['length' => 25])->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // A constant number of queries, regardless of row count (no per-row user lookup).
        $this->assertLessThan(15, $queries);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->fetch(['status' => 'bogus'])->assertStatus(422)->assertJsonValidationErrors('status');
        $this->fetch(['date_from' => 'not-a-date'])->assertStatus(422)->assertJsonValidationErrors('date_from');
        $this->fetch(['date_from' => '2026-05-10', 'date_to' => '2026-05-01'])->assertStatus(422)->assertJsonValidationErrors('date_to');
        $this->fetch(['length' => 7])->assertStatus(422)->assertJsonValidationErrors('length');
        $this->fetch(['order' => [['column' => 1, 'dir' => 'sideways']]])->assertStatus(422);
    }

    public function test_ajax_approve_returns_json_and_updates_the_vendor(): void
    {
        $vendor = Vendor::factory()->create();

        $this->admin()->postJson(route('admin.vendors.approve', $vendor))
            ->assertOk()
            ->assertJson(['message' => 'Vendor approved.', 'status' => 'approved']);

        $this->assertSame(VendorStatus::Approved, $vendor->fresh()->status);
    }

    public function test_ajax_reject_requires_a_reason(): void
    {
        $vendor = Vendor::factory()->create();

        $this->admin()->postJson(route('admin.vendors.reject', $vendor), [])
            ->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->admin()->postJson(route('admin.vendors.reject', $vendor), ['reason' => 'no'])
            ->assertStatus(422)->assertJsonValidationErrors('reason');

        $this->assertSame(VendorStatus::Pending, $vendor->fresh()->status);

        $this->admin()->postJson(route('admin.vendors.reject', $vendor), ['reason' => 'Missing documents.'])
            ->assertOk()->assertJson(['status' => 'rejected']);

        $this->assertSame('Missing documents.', $vendor->fresh()->rejection_reason);
    }

    public function test_ajax_suspend_and_reactivate(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        $this->admin()->postJson(route('admin.vendors.suspend', $vendor))->assertOk()->assertJson(['status' => 'suspended']);
        $this->admin()->postJson(route('admin.vendors.reactivate', $vendor))->assertOk()->assertJson(['status' => 'approved']);
    }

    public function test_ajax_invalid_transition_returns_a_json_error(): void
    {
        $vendor = Vendor::factory()->create();

        $this->admin()->postJson(route('admin.vendors.suspend', $vendor))
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertSame(VendorStatus::Pending, $vendor->fresh()->status);
    }

    public function test_non_admins_cannot_use_the_ajax_actions(): void
    {
        $vendor = Vendor::factory()->create();

        $this->postJson(route('admin.vendors.approve', $vendor))->assertUnauthorized();
        $this->actingAs($vendor->user, 'web')->postJson(route('admin.vendors.approve', $vendor))->assertUnauthorized();

        $this->assertSame(VendorStatus::Pending, $vendor->fresh()->status);
    }
}
