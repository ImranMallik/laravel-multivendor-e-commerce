<?php

namespace Tests\Feature\Admin;

use App\Enums\VendorStatus;
use App\Events\VendorApproved;
use App\Events\VendorRejected;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Vendor\VendorApprovedNotification;
use App\Notifications\Vendor\VendorRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VendorApprovalTest extends TestCase
{
    use RefreshDatabase;

    private ?Admin $admin = null;

    /**
     * One admin per test: auth.session ties the session to a single admin's password hash.
     */
    private function admin(): static
    {
        return $this->actingAs($this->admin ??= Admin::factory()->create(), 'admin');
    }

    public function test_vendor_pages_require_the_admin_guard(): void
    {
        $vendor = Vendor::factory()->create();

        $this->get(route('admin.vendors.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.vendors.approve', $vendor))->assertRedirect(route('admin.login'));

        // A signed-in customer or vendor on the web guard is not an admin.
        $this->actingAs(User::factory()->create(), 'web')->get(route('admin.vendors.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($vendor->user, 'web')->post(route('admin.vendors.approve', $vendor))->assertRedirect(route('admin.login'));

        $this->assertSame(VendorStatus::Pending, $vendor->fresh()->status);
    }

    public function test_list_page_renders_with_status_tabs_and_filters(): void
    {
        Vendor::factory()->count(2)->create();
        Vendor::factory()->approved()->create();

        $this->admin()->get(route('admin.vendors.index'))
            ->assertOk()
            ->assertSee('id="vendors-table"', false)
            ->assertSee('data-source="'.route('admin.vendors.data').'"', false)
            ->assertSee('id="filter-status"', false)
            ->assertSee('data-count="pending">2<', false)
            ->assertSee('data-count="approved">1<', false)
            ->assertSee('data-count="all">3<', false);
    }

    public function test_detail_page_renders(): void
    {
        $vendor = Vendor::factory()->create(['shop_name' => 'Detail Shop']);

        $this->admin()->get(route('admin.vendors.show', $vendor))->assertOk()->assertSee('Detail Shop')->assertSee($vendor->user->email);
    }

    public function test_admin_can_approve_a_vendor_and_the_vendor_is_notified(): void
    {
        Event::fake([VendorApproved::class]);
        $vendor = Vendor::factory()->create();

        $this->admin()->post(route('admin.vendors.approve', $vendor))->assertSessionHas('success');

        $vendor->refresh();
        $this->assertSame(VendorStatus::Approved, $vendor->status);
        $this->assertNotNull($vendor->approved_at);
        Event::assertDispatched(VendorApproved::class);
    }

    public function test_approval_email_is_sent_to_the_vendor(): void
    {
        Notification::fake();
        $vendor = Vendor::factory()->create();

        $this->admin()->post(route('admin.vendors.approve', $vendor));

        Notification::assertSentTo($vendor->user, VendorApprovedNotification::class);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $vendor = Vendor::factory()->create();

        $this->admin()->post(route('admin.vendors.reject', $vendor), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->admin()->post(route('admin.vendors.reject', $vendor), ['reason' => 'no'])->assertSessionHasErrors('reason');

        $this->assertSame(VendorStatus::Pending, $vendor->fresh()->status);
    }

    public function test_admin_can_reject_with_a_reason_and_the_vendor_is_notified(): void
    {
        Event::fake([VendorRejected::class]);
        $vendor = Vendor::factory()->create();

        $this->admin()->post(route('admin.vendors.reject', $vendor), ['reason' => 'Documents are incomplete.'])
            ->assertSessionHas('success');

        $vendor->refresh();
        $this->assertSame(VendorStatus::Rejected, $vendor->status);
        $this->assertSame('Documents are incomplete.', $vendor->rejection_reason);
        Event::assertDispatched(VendorRejected::class);
    }

    public function test_rejection_email_contains_the_reason(): void
    {
        Notification::fake();
        $vendor = Vendor::factory()->create();

        $this->admin()->post(route('admin.vendors.reject', $vendor), ['reason' => 'Documents are incomplete.']);

        Notification::assertSentTo($vendor->user, VendorRejectedNotification::class, function ($notification) use ($vendor) {
            return str_contains(implode(' ', $notification->toMail($vendor->user)->introLines), 'Documents are incomplete.');
        });
    }

    public function test_admin_can_suspend_and_reactivate(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        $this->admin()->post(route('admin.vendors.suspend', $vendor));
        $this->assertSame(VendorStatus::Suspended, $vendor->fresh()->status);

        $this->actingAs($vendor->user, 'web')->get(route('vendor.dashboard'))->assertRedirect(route('vendor.status'));

        $this->admin()->post(route('admin.vendors.reactivate', $vendor));
        $this->assertSame(VendorStatus::Approved, $vendor->fresh()->status);

        // Fresh instance: the earlier request cached the suspended vendor on the old user model.
        $this->actingAs($vendor->user->fresh(), 'web')->get(route('vendor.dashboard'))->assertOk();
    }

    public function test_invalid_transitions_are_refused(): void
    {
        $pending = Vendor::factory()->create();
        $approved = Vendor::factory()->approved()->create();

        $this->admin()->post(route('admin.vendors.suspend', $pending))->assertSessionHas('error');
        $this->admin()->post(route('admin.vendors.reactivate', $approved))->assertSessionHas('error');

        $this->assertSame(VendorStatus::Pending, $pending->fresh()->status);
        $this->assertSame(VendorStatus::Approved, $approved->fresh()->status);
    }

    public function test_rejected_vendor_can_be_approved_later_and_reason_is_cleared(): void
    {
        $vendor = Vendor::factory()->rejected()->create();

        $this->admin()->post(route('admin.vendors.approve', $vendor));

        $vendor->refresh();
        $this->assertSame(VendorStatus::Approved, $vendor->status);
        $this->assertNull($vendor->rejection_reason);
    }
}
