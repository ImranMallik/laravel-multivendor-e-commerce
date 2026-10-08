<?php

namespace Tests\Feature\Frontend;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'phone' => '555-0100',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ], $overrides);
    }

    public function test_auth_pages_render(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Forgot password?');
        $this->get(route('register'))->assertOk()->assertSee('I want to sell')->assertSee('Create Customer Account');
        $this->get(route('register.vendor'))->assertOk()->assertSee('Create Seller Account');
        $this->get(route('password.request'))->assertOk();
    }

    public function test_customer_registers_and_lands_on_account_dashboard(): void
    {
        $this->post(route('register.store'), $this->registrationData())
            ->assertRedirect(route('account.dashboard'));

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame(UserStatus::Active, $user->fresh()->status);
        $this->assertNull($user->vendor);

        $this->get(route('account.dashboard'))->assertOk()->assertSee('Jane Customer');
    }

    public function test_role_cannot_be_injected_on_customer_register(): void
    {
        foreach (['vendor', 'admin', 'Vendor'] as $index => $role) {
            $this->post(route('register.store'), $this->registrationData([
                'email' => "inject{$index}@example.com",
                'role' => $role,
                'status' => 'blocked',
            ]));

            $user = User::firstWhere('email', "inject{$index}@example.com");

            $this->assertSame(UserRole::Customer, $user->role);
            $this->assertSame(UserStatus::Active, $user->status);

            $this->post(route('logout'));
        }

        $this->assertSame(0, Vendor::count());
    }

    public function test_registration_validation(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('register.store'), $this->registrationData(['email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');
        $this->post(route('register.store'), $this->registrationData(['password' => 'onlyletters', 'password_confirmation' => 'onlyletters']))
            ->assertSessionHasErrors('password');
        $this->post(route('register.store'), $this->registrationData(['password_confirmation' => 'different1']))
            ->assertSessionHasErrors('password');

        $this->assertGuest('web');
    }

    public function test_customer_login_redirects_to_account_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_blocked_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Blocked]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Your account has been blocked. Please contact support.']);

        $this->assertGuest('web');
    }

    public function test_user_blocked_during_a_session_is_signed_out(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Blocked]);

        $this->actingAs($user)->get(route('account.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customer_can_logout(): void
    {
        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest('web');
    }

    public function test_guest_is_redirected_to_frontend_login(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
        $this->get(route('account.orders'))->assertRedirect(route('login'));
        $this->get('/vendor/dashboard')->assertRedirect(route('login'));
    }

    public function test_guest_visiting_admin_goes_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/vendors')->assertRedirect(route('admin.login'));
    }

    public function test_signed_in_users_are_sent_to_their_own_dashboard_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect(route('account.dashboard'));

        $vendor = Vendor::factory()->approved()->create();
        $this->actingAs($vendor->user)->get(route('register'))->assertRedirect(route('vendor.dashboard'));
    }

    public function test_customer_can_update_profile_and_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'New Name', 'email' => 'new@example.com', 'phone' => '123',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'new@example.com']);

        $this->actingAs($user)->put(route('account.profile.password'), [
            'current_password' => 'wrong', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertSessionHasErrors('current_password', null, 'updatePassword');

        $this->actingAs($user)->put(route('account.profile.password'), [
            'current_password' => 'password', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
    }

    public function test_password_reset_link_can_be_requested_and_used(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'Brandnew123',
                'password_confirmation' => 'Brandnew123',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('Brandnew123', $user->fresh()->password));
    }
}
