<?php

namespace Tests\Feature\Frontend;

use App\Actions\Vendor\RegisterVendorAction;
use App\Enums\VendorSource;
use App\Enums\VendorStatus;
use App\Events\VendorApplicationSubmitted;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Vendor\NewVendorApplication;
use App\Notifications\Vendor\VendorApplicationReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function vendorData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Vic Vendor',
            'email' => 'vic@example.com',
            'phone' => '555-0111',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'shop_name' => 'Vic Gadgets',
            'shop_phone' => '555-0222',
            'address' => '1 Market Street',
        ], $overrides);
    }

    public function test_vendor_registration_creates_a_customer_user_and_pending_application(): void
    {
        Event::fake([VendorApplicationSubmitted::class]);

        $this->post(route('register.vendor.store'), $this->vendorData())
            ->assertRedirect(route('account.seller.show'));

        $user = User::where('email', 'vic@example.com')->firstOrFail();
        $vendor = $user->vendor;

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame(VendorStatus::Pending, $vendor->status);
        $this->assertSame(VendorSource::Application, $vendor->source);
        $this->assertNotNull($vendor->applied_at);
        $this->assertSame('Vic Gadgets', $vendor->shop_name);
        $this->assertSame('vic-gadgets', $vendor->slug);
        $this->assertNull($vendor->approved_at);
        $this->assertNull($vendor->created_by);

        $log = $vendor->statusLogs()->sole();
        $this->assertNull($log->from_status);
        $this->assertSame(VendorStatus::Pending, $log->to_status);

        Event::assertDispatched(VendorApplicationSubmitted::class, fn ($event) => $event->vendor->is($vendor));
    }

    public function test_registration_is_atomic_when_the_vendor_row_fails(): void
    {
        // Force a failure after the user has been created.
        Vendor::creating(fn () => throw new \RuntimeException('boom'));

        try {
            app(RegisterVendorAction::class)->execute($this->vendorData());
            $this->fail('Expected the action to throw.');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertDatabaseMissing('users', ['email' => 'vic@example.com']);
        $this->assertSame(0, Vendor::count());
    }

    public function test_status_and_ownership_cannot_be_injected_on_vendor_register(): void
    {
        $this->post(route('register.vendor.store'), $this->vendorData([
            'status' => 'approved',
            'source' => 'admin',
            'user_id' => 999,
            'created_by' => 1,
        ]));

        $vendor = User::firstWhere('email', 'vic@example.com')->vendor;

        $this->assertSame(VendorStatus::Pending, $vendor->status);
        $this->assertSame(VendorSource::Application, $vendor->source);
        $this->assertNull($vendor->created_by);
    }

    public function test_shop_slugs_stay_unique(): void
    {
        $first = Vendor::factory()->create(['shop_name' => 'Same Name', 'slug' => 'same-name']);
        // The slug is derived from the shop name; direct creation (the validation rule would block this in a form).
        $second = app(RegisterVendorAction::class)->execute($this->vendorData(['shop_name' => 'Same Name']));

        $this->assertSame('same-name', $first->slug);
        $this->assertSame('same-name-2', $second->slug);
    }

    public function test_vendor_registration_validates_shop_fields(): void
    {
        $this->post(route('register.vendor.store'), $this->vendorData(['shop_name' => '', 'address' => '']))
            ->assertSessionHasErrors(['shop_name', 'address']);

        Vendor::factory()->create(['shop_name' => 'Taken Name']);
        $this->post(route('register.vendor.store'), $this->vendorData(['shop_name' => 'Taken Name']))
            ->assertSessionHasErrors('shop_name');

        // Only the factory's owner of "Taken Name" exists: neither attempt created an account.
        $this->assertSame(1, User::count());
        $this->assertSame(1, Vendor::count());
    }

    public function test_admins_and_the_applicant_are_notified_of_a_new_application(): void
    {
        Notification::fake();
        $admin = Admin::factory()->create();

        $this->post(route('register.vendor.store'), $this->vendorData());

        Notification::assertSentTo($admin, NewVendorApplication::class);
        Notification::assertSentTo(User::firstWhere('email', 'vic@example.com'), VendorApplicationReceivedNotification::class);
    }

    public function test_the_admin_bell_gets_a_database_notification(): void
    {
        $admin = Admin::factory()->create();

        $this->post(route('register.vendor.store'), $this->vendorData());

        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->assertStringContainsString('Vic Gadgets', $admin->unreadNotifications->first()->data['message']);
    }

    public function test_login_redirects_by_shop_status(): void
    {
        $approved = Vendor::factory()->approved()->create();
        $pending = Vendor::factory()->create();
        $customer = User::factory()->create();

        $this->post(route('login.store'), ['email' => $approved->user->email, 'password' => 'password'])
            ->assertRedirect(route('vendor.dashboard'));
        auth('web')->logout();

        $this->post(route('login.store'), ['email' => $pending->user->email, 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));
        auth('web')->logout();

        $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_pending_rejected_and_suspended_users_cannot_open_the_vendor_panel(): void
    {
        $cases = [
            'pending' => [Vendor::factory()->create(), 'Your application is under review'],
            'rejected' => [Vendor::factory()->rejected('Bad documents here')->create(), 'Bad documents here'],
            'suspended' => [Vendor::factory()->suspended()->create(), 'Shop suspended'],
        ];

        foreach ($cases as [$vendor, $text]) {
            $this->actingAs($vendor->user)->get(route('account.seller.show'))->assertOk()->assertSee($text);

            foreach (['vendor.dashboard', 'vendor.shop.edit', 'vendor.profile.edit', 'vendor.products.index'] as $route) {
                $this->actingAs($vendor->user)->get(route($route))->assertRedirect(route('account.seller.show'));
            }

            $this->actingAs($vendor->user)->put(route('vendor.shop.update'), [
                'shop_name' => 'Hacked', 'address' => 'x',
            ])->assertRedirect(route('account.seller.show'));

            $this->assertNotSame('Hacked', $vendor->fresh()->shop_name);
        }
    }

    public function test_approved_vendor_reaches_the_dashboard(): void
    {
        $vendor = Vendor::factory()->approved()->create(['shop_name' => 'Happy Shop']);

        $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk()->assertSee('Happy Shop');
        $this->actingAs($vendor->user)->get(route('vendor.orders'))->assertOk()->assertSee('coming soon');
        // The old status URL now leads to the account page.
        $this->actingAs($vendor->user)->get(route('vendor.status'))->assertRedirect('/account/seller');
    }

    public function test_a_user_without_a_shop_is_sent_to_become_a_seller(): void
    {
        $customer = User::factory()->create();

        foreach (['vendor.dashboard', 'vendor.shop.edit', 'vendor.profile.edit'] as $route) {
            $this->actingAs($customer)->get(route($route))->assertRedirect(route('account.seller.show'));
        }
    }

    public function test_a_vendor_can_still_use_the_customer_account_and_shop(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        foreach (['account.dashboard', 'account.orders', 'account.wishlist', 'account.profile.edit', 'account.seller.show'] as $route) {
            $this->actingAs($vendor->user)->get(route($route))->assertOk();
        }

        $this->actingAs($vendor->user)->get(route('home'))->assertOk();
        $this->actingAs($vendor->user)->get(route('shop.index'))->assertOk();
    }

    public function test_navigation_links_follow_the_shop_state(): void
    {
        $customer = User::factory()->create();
        $vendor = Vendor::factory()->approved()->create();

        $this->actingAs($customer)->get(route('account.dashboard'))
            ->assertSee(route('account.seller.show'), false)
            ->assertSee('Become a Seller')
            ->assertDontSee('Seller dashboard');

        $this->actingAs($vendor->user)->get(route('account.dashboard'))
            ->assertSee('Seller dashboard')
            ->assertSee(route('vendor.dashboard'), false)
            ->assertDontSee('Become a Seller');

        $this->actingAs($customer)->get('/')->assertSee('become a seller');
        $this->actingAs($vendor->user)->get('/')->assertSee('seller dashboard');

        $this->actingAs($vendor->user)->get(route('vendor.dashboard'))
            ->assertSee('Switch to shopping');
    }

    public function test_vendor_updates_only_their_own_shop_and_logo_is_replaced(): void
    {
        Storage::fake('public');
        $mine = Vendor::factory()->approved()->create();
        $other = Vendor::factory()->approved()->create(['shop_name' => 'Other Shop']);

        $this->actingAs($mine->user)->put(route('vendor.shop.update'), [
            'shop_name' => 'My New Name',
            'address' => '2 New Road',
            'description' => 'Hello',
            'logo' => UploadedFile::fake()->image('logo.png'),
            // Attempts to target someone else's record must be ignored.
            'id' => $other->id,
            'vendor_id' => $other->id,
            'status' => 'suspended',
        ])->assertSessionHasNoErrors();

        $mine->refresh();
        $this->assertSame('My New Name', $mine->shop_name);
        $this->assertSame(VendorStatus::Approved, $mine->status);
        $this->assertSame('Other Shop', $other->fresh()->shop_name);
        Storage::disk('public')->assertExists($mine->logo);

        $oldLogo = $mine->logo;
        $this->actingAs($mine->user)->put(route('vendor.shop.update'), [
            'shop_name' => 'My New Name', 'address' => '2 New Road',
            'logo' => UploadedFile::fake()->image('second.jpg'),
        ]);

        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertExists($mine->fresh()->logo);
    }

    public function test_vendor_can_update_profile_and_password(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        $this->actingAs($vendor->user)->put(route('vendor.profile.update'), [
            'name' => 'Renamed', 'email' => $vendor->user->email, 'phone' => '9',
        ])->assertSessionHas('success');

        $this->actingAs($vendor->user)->put(route('vendor.profile.password'), [
            'current_password' => 'password', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertSessionHas('success');

        $this->assertSame('Renamed', $vendor->user->fresh()->name);
    }
}
