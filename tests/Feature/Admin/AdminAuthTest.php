<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('admin.login'))->assertOk()->assertSee('Admin Login');
    }

    public function test_admin_can_login_and_see_dashboard(): void
    {
        $admin = Admin::factory()->create();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.dashboard'))->assertOk()->assertSee($admin->name);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $admin = Admin::factory()->create();

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'wrong',
        ])->assertRedirect(route('admin.login'))->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_inactive_admin_cannot_login(): void
    {
        $admin = Admin::factory()->inactive()->create();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $admin = Admin::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'wrong']);
        }

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_admin_can_logout(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }

    public function test_logged_in_admin_is_redirected_away_from_login(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_customer_on_web_guard_cannot_access_admin_area(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_session_does_not_grant_web_guard_access(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');
        // actingAs() switches the default guard; restore it to behave like a real request.
        auth()->shouldUse('web');

        $this->get(route('account.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_role_middleware_allows_only_matching_role(): void
    {
        Route::middleware(['web', 'auth', 'role:vendor'])
            ->get('/_vendor-test', fn () => 'ok');

        $this->actingAs(User::factory()->create(['role' => UserRole::Customer]))
            ->get('/_vendor-test')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => UserRole::Vendor]))
            ->get('/_vendor-test')->assertOk();
    }
}
