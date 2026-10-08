<?php

namespace Tests\Feature\Frontend;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_session_is_not_a_customer_session(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        auth()->shouldUse('web');

        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

    public function test_customer_session_is_not_an_admin_session(): void
    {
        $this->actingAs(User::factory()->create(), 'web');

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.profile.edit'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_admin_can_still_open_the_customer_login(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        auth()->shouldUse('web');

        $this->get(route('login'))->assertOk();
    }

    public function test_customer_can_still_open_the_admin_login(): void
    {
        $this->actingAs(User::factory()->create(), 'web');
        auth()->shouldUse('admin');

        $this->get(route('admin.login'))->assertOk();
    }

    public function test_customer_logout_keeps_admin_logged_in(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->actingAs(User::factory()->create(), 'web');

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_logout_keeps_customer_logged_in(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer, 'web')->actingAs(Admin::factory()->create(), 'admin');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($customer, 'web');
    }
}
