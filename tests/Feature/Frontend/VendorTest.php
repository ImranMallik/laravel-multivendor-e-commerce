<?php

namespace Tests\Feature\Frontend;

use App\Actions\Vendor\RegisterVendorAction;
use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Events\VendorRegistered;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Vendor\NewVendorRegistered;
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

    public function test_vendor_registration_creates_user_and_pending_vendor(): void
    {
        Event::fake([VendorRegistered::class]);

        $this->post(route('register.vendor.store'), $this->vendorData())
            ->assertRedirect(route('vendor.status'));

        $user = User::where('email', 'vic@example.com')->firstOrFail();
        $vendor = $user->vendor;

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame(UserRole::Vendor, $user->role);
        $this->assertSame(VendorStatus::Pending, $vendor->status);
        $this->assertSame('Vic Gadgets', $vendor->shop_name);
        $this->assertSame('vic-gadgets', $vendor->slug);
        $this->assertNull($vendor->approved_at);

        Event::assertDispatched(VendorRegistered::class, fn ($event) => $event->vendor->is($vendor));
    }

    public function test_registration_is_atomic_when_the_vendor_row_fails(): void
    {
        // A duplicate user_id/slug collision is not reachable here, so force a failure after the user is created.
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

    public function test_role_and_status_cannot_be_injected_on_vendor_register(): void
    {
        $this->post(route('register.vendor.store'), $this->vendorData([
            'role' => 'customer',
            'status' => 'approved',
            'user_id' => 999,
        ]));

        $user = User::firstWhere('email', 'vic@example.com');

        $this->assertSame(UserRole::Vendor, $user->role);
        $this->assertSame(VendorStatus::Pending, $user->vendor->status);
    }

    public function test_shop_slugs_stay_unique(): void
    {
        $first = Vendor::factory()->create(['shop_name' => 'Same Name', 'slug' => 'same-name']);
        $second = app(RegisterVendorAction::class)->execute($this->vendorData(['shop_name' => 'Same Name']));

        $this->assertSame('same-name', $first->slug);
        $this->assertSame('same-name-2', $second->slug);
    }

    public function test_vendor_registration_validates_shop_fields(): void
    {
        $this->post(route('register.vendor.store'), $this->vendorData(['shop_name' => '', 'address' => '']))
            ->assertSessionHasErrors(['shop_name', 'address']);

        $this->assertSame(0, User::count());
    }

    public function test_admins_are_notified_of_a_new_vendor(): void
    {
        Notification::fake();
        $admin = Admin::factory()->create();

        $this->post(route('register.vendor.store'), $this->vendorData());

        Notification::assertSentTo($admin, NewVendorRegistered::class);
    }

    public function test_vendor_login_redirects_to_vendor_dashboard(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        $this->post(route('login.store'), ['email' => $vendor->user->email, 'password' => 'password'])
            ->assertRedirect(route('vendor.dashboard'));
    }

    public function test_non_approved_vendors_only_see_the_status_page(): void
    {
        $cases = [
            'pending' => [Vendor::factory()->create(), 'Waiting for approval'],
            'rejected' => [Vendor::factory()->rejected('Bad documents here')->create(), 'Bad documents here'],
            'suspended' => [Vendor::factory()->suspended()->create(), 'Shop suspended'],
        ];

        foreach ($cases as [$vendor, $text]) {
            $this->actingAs($vendor->user)->get(route('vendor.status'))->assertOk()->assertSee($text);

            foreach (['vendor.dashboard', 'vendor.shop.edit', 'vendor.profile.edit', 'vendor.products'] as $route) {
                $this->actingAs($vendor->user)->get(route($route))->assertRedirect(route('vendor.status'));
            }

            $this->actingAs($vendor->user)->put(route('vendor.shop.update'), [
                'shop_name' => 'Hacked', 'address' => 'x',
            ])->assertRedirect(route('vendor.status'));

            $this->assertNotSame('Hacked', $vendor->fresh()->shop_name);
        }
    }

    public function test_approved_vendor_reaches_the_dashboard_and_is_redirected_from_status(): void
    {
        $vendor = Vendor::factory()->approved()->create(['shop_name' => 'Happy Shop']);

        $this->actingAs($vendor->user)->get(route('vendor.dashboard'))->assertOk()->assertSee('Happy Shop');
        $this->actingAs($vendor->user)->get(route('vendor.products'))->assertOk()->assertSee('coming soon');
        $this->actingAs($vendor->user)->get(route('vendor.status'))->assertRedirect(route('vendor.dashboard'));
    }

    public function test_customer_cannot_open_vendor_area_and_vendor_cannot_open_customer_area(): void
    {
        $customer = User::factory()->create();
        $vendor = Vendor::factory()->approved()->create();

        foreach (['vendor.dashboard', 'vendor.status', 'vendor.shop.edit'] as $route) {
            $this->actingAs($customer)->get(route($route))->assertForbidden();
        }

        foreach (['account.dashboard', 'account.orders', 'account.profile.edit'] as $route) {
            $this->actingAs($vendor->user)->get(route($route))->assertForbidden();
        }
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
