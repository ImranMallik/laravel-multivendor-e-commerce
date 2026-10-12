<?php

namespace Tests\Feature\Vendor;

use App\Enums\UserStatus;
use App\Enums\VendorSource;
use App\Enums\VendorStatus;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Vendor\NewVendorApplication;
use App\Notifications\Vendor\VendorApplicationReceivedNotification;
use App\Notifications\Vendor\VendorApprovedNotification;
use App\Notifications\Vendor\VendorReactivatedNotification;
use App\Notifications\Vendor\VendorRejectedNotification;
use App\Notifications\Vendor\VendorSuspendedNotification;
use App\Notifications\Vendor\VendorWelcomeNotification;
use App\Services\VendorStatusService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorOnboardingTest extends TestCase
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function adminData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nina Newowner',
            'email' => 'nina@example.com',
            'phone' => '555-0100',
            'shop_name' => 'Nina Goods',
            'shop_phone' => '555-0101',
            'address' => '9 Harbor Road',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function shopData(array $overrides = []): array
    {
        return array_merge([
            'shop_name' => 'Casey Corner',
            'shop_phone' => '555-0200',
            'address' => '5 Main Street',
        ], $overrides);
    }

    /* ---------- Path 1: admin creates a vendor ---------- */

    public function test_only_admins_can_use_the_create_vendor_routes(): void
    {
        $this->get(route('admin.vendors.create'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.vendors.store'), $this->adminData())->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create(), 'web');
        $this->get(route('admin.vendors.create'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.vendors.store'), $this->adminData())->assertRedirect(route('admin.login'));

        $this->assertSame(0, Vendor::count());
    }

    public function test_create_form_and_list_button_render(): void
    {
        $this->admin()->get(route('admin.vendors.index'))->assertOk()->assertSee('Add Vendor')->assertSee(route('admin.vendors.create'), false);

        $this->admin()->get(route('admin.vendors.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="shop_name"', false)
            ->assertSee('name="shop_phone"', false)
            ->assertSee('name="address"', false)
            ->assertSee('name="logo"', false)
            ->assertSee('data-preview-target="vendor-logo-preview"', false);
    }

    public function test_admin_creates_a_vendor_for_a_new_email(): void
    {
        Notification::fake();

        $response = $this->admin()->post(route('admin.vendors.store'), $this->adminData(['logo' => UploadedFile::fake()->image('logo.png')]));

        $user = User::where('email', 'nina@example.com')->firstOrFail();
        $vendor = $user->vendor;

        $response->assertRedirect(route('admin.vendors.show', $vendor))->assertSessionHas('success');

        $this->assertSame('Nina Newowner', $user->name);
        $this->assertSame(VendorStatus::Approved, $vendor->status);
        $this->assertSame(VendorSource::Admin, $vendor->source);
        $this->assertSame($this->admin->id, $vendor->created_by);
        $this->assertSame($this->admin->id, $vendor->reviewed_by);
        $this->assertNotNull($vendor->approved_at);
        $this->assertNotNull($vendor->reviewed_at);
        $this->assertNull($vendor->applied_at);
        $this->assertStringStartsWith('vendors/logos/', $vendor->logo);
        Storage::disk('public')->assertExists($vendor->logo);

        $log = $vendor->statusLogs()->sole();
        $this->assertNull($log->from_status);
        $this->assertSame(VendorStatus::Approved, $log->to_status);
        $this->assertSame('Created by admin', $log->note);
        $this->assertSame($this->admin->id, $log->admin_id);

        // The new user can sign in as a seller straight away (approved shop).
        $this->assertSame(route('vendor.dashboard'), $user->homeRoute());
    }

    public function test_a_new_vendor_gets_an_unusable_password_and_a_set_password_link_only(): void
    {
        Notification::fake();

        $this->admin()->post(route('admin.vendors.store'), $this->adminData());

        $user = User::where('email', 'nina@example.com')->firstOrFail();

        // A random 64-character secret was hashed and discarded: it is not any password a person could type.
        $this->assertNotEmpty($user->password);
        foreach (['password', 'Password1', 'nina@example.com', 'Nina Goods', 'secret'] as $guess) {
            $this->assertFalse(Hash::check($guess, $user->password));
        }

        $url = null;
        Notification::assertSentTo($user, VendorWelcomeNotification::class, function ($notification) use ($user, &$url) {
            $mail = $notification->toMail($user);
            $url = $mail->actionUrl;

            // The mail has a link and no plain password.
            $this->assertSame('Set your password', $mail->actionText);
            $this->assertStringNotContainsString('Your password is', implode(' ', $mail->introLines));

            return true;
        });

        $this->assertStringContainsString('/reset-password/', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $token = basename((string) parse_url($url, PHP_URL_PATH));

        // The link really lets the owner choose a password.
        // actingAs() switched the default guard to admin; a visitor following the email link is a guest on web.
        auth()->shouldUse('web');
        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'nina@example.com',
            'password' => 'MyOwnPass123',
            'password_confirmation' => 'MyOwnPass123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('MyOwnPass123', $user->fresh()->password));
    }

    public function test_admin_attaches_a_shop_to_an_existing_customer(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['name' => 'Existing Eve', 'email' => 'eve@example.com', 'phone' => '111']);
        $oldHash = $customer->password;

        $this->admin()->post(route('admin.vendors.store'), $this->adminData([
            'name' => 'Different Name', 'email' => 'eve@example.com', 'phone' => '999',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, User::count());
        $customer->refresh();
        $this->assertSame('Existing Eve', $customer->name);
        $this->assertSame('111', $customer->phone);
        $this->assertSame($oldHash, $customer->password);
        $this->assertSame('Nina Goods', $customer->vendor->shop_name);
        $this->assertSame(VendorStatus::Approved, $customer->vendor->status);

        // No set-password link for someone who already has a password.
        Notification::assertSentTo($customer, VendorWelcomeNotification::class, fn ($n) => $n->toMail($customer)->actionText === 'Open seller dashboard');
    }

    public function test_admin_cannot_create_a_second_shop_for_a_user_who_has_one(): void
    {
        $existing = Vendor::factory()->approved()->create(['shop_name' => 'First Shop']);

        $this->admin()->post(route('admin.vendors.store'), $this->adminData(['email' => $existing->user->email]))
            ->assertSessionHasErrors('email')
            ->assertSessionHas('existing_vendor_id', $existing->id);

        $this->assertSame(1, Vendor::count());

        // The form shows a link to the existing vendor.
        $this->admin()->withSession(['existing_vendor_id' => $existing->id])
            ->get(route('admin.vendors.create'))
            ->assertSee(route('admin.vendors.show', $existing), false);
    }

    public function test_admin_cannot_create_a_shop_for_a_blocked_user(): void
    {
        $blocked = User::factory()->create(['status' => UserStatus::Blocked]);

        $this->admin()->post(route('admin.vendors.store'), $this->adminData(['email' => $blocked->email]))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, Vendor::count());
    }

    public function test_admin_vendor_form_validation(): void
    {
        Vendor::factory()->create(['shop_name' => 'Taken Shop']);

        foreach ([
            'name' => ['name' => ''],
            'email' => ['email' => 'not-an-email'],
            'shop_name' => ['shop_name' => ''],
            'address' => ['address' => ''],
        ] as $field => $override) {
            $this->admin()->post(route('admin.vendors.store'), $this->adminData($override))->assertSessionHasErrors($field);
        }

        $this->admin()->post(route('admin.vendors.store'), $this->adminData(['shop_name' => 'Taken Shop']))->assertSessionHasErrors('shop_name');
        $this->admin()->post(route('admin.vendors.store'), $this->adminData(['address' => str_repeat('a', 501)]))->assertSessionHasErrors('address');
        $this->admin()->post(route('admin.vendors.store'), $this->adminData(['phone' => str_repeat('1', 21)]))->assertSessionHasErrors('phone');
        $this->admin()->post(route('admin.vendors.store'), $this->adminData(['logo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))->assertSessionHasErrors('logo');
        $this->admin()->post(route('admin.vendors.store'), $this->adminData(['logo' => UploadedFile::fake()->image('big.png')->size(2000)]))->assertSessionHasErrors('logo');

        $this->assertSame(1, Vendor::count());
        $this->assertSame(1, User::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_failed_creation_leaves_nothing_behind(): void
    {
        Vendor::creating(fn () => throw new \RuntimeException('boom'));

        // actingAs() switched the default guard to admin; a visitor following the email link is a guest on web.
        auth()->shouldUse('web');

        try {
            $this->admin()->post(route('admin.vendors.store'), $this->adminData(['logo' => UploadedFile::fake()->image('logo.png')]));
            $this->fail('Expected an exception.');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertDatabaseMissing('users', ['email' => 'nina@example.com']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /* ---------- Path 2: a user applies ---------- */

    public function test_a_customer_applies_from_my_account(): void
    {
        Notification::fake();
        $admin = Admin::factory()->create();
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('account.seller.show'))
            ->assertOk()
            ->assertSee('name="shop_name"', false)
            ->assertSee('name="logo"', false)
            ->assertSee(route('account.seller.apply'), false);

        $this->actingAs($customer)->post(route('account.seller.apply'), $this->shopData(['logo' => UploadedFile::fake()->image('l.png')]))
            ->assertRedirect(route('account.seller.show'))
            ->assertSessionHas('success');

        $vendor = $customer->fresh()->vendor;

        $this->assertSame(VendorStatus::Pending, $vendor->status);
        $this->assertSame(VendorSource::Application, $vendor->source);
        $this->assertNotNull($vendor->applied_at);
        $this->assertSame('Casey Corner', $vendor->shop_name);
        $this->assertSame('555-0200', $vendor->phone);
        Storage::disk('public')->assertExists($vendor->logo);
        $this->assertSame('Application submitted', $vendor->statusLogs()->sole()->note);

        Notification::assertSentTo($admin, NewVendorApplication::class);
        Notification::assertSentTo($customer, VendorApplicationReceivedNotification::class);

        // The page now shows the pending state with the submitted details, and no form.
        $this->actingAs($customer->fresh())->get(route('account.seller.show'))
            ->assertSee('Your application is under review')
            ->assertSee('Casey Corner')
            ->assertSee('5 Main Street')
            ->assertDontSee('name="shop_name"', false);

        // The customer can still shop; only the vendor panel is locked.
        $this->actingAs($customer->fresh())->get(route('account.dashboard'))->assertOk();
        $this->actingAs($customer->fresh())->get(route('vendor.dashboard'))->assertRedirect(route('account.seller.show'));
    }

    public function test_application_validation(): void
    {
        $customer = User::factory()->create();
        Vendor::factory()->create(['shop_name' => 'Taken Shop']);

        $this->actingAs($customer)->post(route('account.seller.apply'), $this->shopData(['shop_name' => '']))->assertSessionHasErrors('shop_name');
        $this->actingAs($customer)->post(route('account.seller.apply'), $this->shopData(['shop_name' => 'Taken Shop']))->assertSessionHasErrors('shop_name');
        $this->actingAs($customer)->post(route('account.seller.apply'), $this->shopData(['address' => '']))->assertSessionHasErrors('address');
        $this->actingAs($customer)->post(route('account.seller.apply'), $this->shopData(['logo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))->assertSessionHasErrors('logo');

        $this->assertNull($customer->fresh()->vendor);
    }

    public function test_guests_cannot_reach_the_seller_page(): void
    {
        $this->get(route('account.seller.show'))->assertRedirect(route('login'));
        $this->post(route('account.seller.apply'), $this->shopData())->assertRedirect(route('login'));
        $this->put(route('account.seller.reapply'), $this->shopData())->assertRedirect(route('login'));
    }

    public function test_duplicate_applications_are_blocked(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post(route('account.seller.apply'), $this->shopData());
        $this->assertSame(1, Vendor::count());

        // Pending
        $this->actingAs($customer->fresh())->post(route('account.seller.apply'), $this->shopData(['shop_name' => 'Second Try']))
            ->assertRedirect(route('account.seller.show'))
            ->assertSessionHas('error');

        // Approved
        $customer->vendor->forceFill(['status' => VendorStatus::Approved])->save();
        $this->actingAs($customer->fresh())->post(route('account.seller.apply'), $this->shopData(['shop_name' => 'Third Try']))
            ->assertSessionHas('error');

        $this->assertSame(1, Vendor::count());
        $this->assertSame('Casey Corner', $customer->fresh()->vendor->shop_name);
    }

    public function test_a_rejected_vendor_can_reapply_and_the_log_is_written(): void
    {
        Notification::fake();
        $admin = Admin::factory()->create();
        $vendor = Vendor::factory()->rejected('Photos are missing.')->create(['shop_name' => 'Old Name', 'applied_at' => now()->subWeek()]);
        $oldApplied = $vendor->applied_at;

        $this->actingAs($vendor->user)->get(route('account.seller.show'))
            ->assertOk()
            ->assertSee('Application not approved')
            ->assertSee('Photos are missing.')
            ->assertSee('value="Old Name"', false)
            ->assertSee('Re-apply');

        $this->actingAs($vendor->user)->put(route('account.seller.reapply'), $this->shopData(['shop_name' => 'Better Name', 'logo' => UploadedFile::fake()->image('new.png')]))
            ->assertRedirect(route('account.seller.show'))
            ->assertSessionHas('success');

        $vendor->refresh();

        $this->assertSame(VendorStatus::Pending, $vendor->status);
        $this->assertSame('Better Name', $vendor->shop_name);
        $this->assertNull($vendor->rejection_reason);
        $this->assertTrue($vendor->applied_at->gt($oldApplied));
        Storage::disk('public')->assertExists($vendor->logo);

        $log = $vendor->statusLogs()->first();
        $this->assertSame(VendorStatus::Rejected, $log->from_status);
        $this->assertSame(VendorStatus::Pending, $log->to_status);
        $this->assertSame('Re-applied', $log->note);
        $this->assertNull($log->admin_id);

        Notification::assertSentTo($admin, NewVendorApplication::class);
    }

    public function test_a_reapplication_can_keep_the_same_shop_name_and_replaces_the_old_logo(): void
    {
        $vendor = Vendor::factory()->rejected()->create(['shop_name' => 'Same Name']);
        $this->actingAs($vendor->user)->put(route('account.seller.reapply'), $this->shopData(['shop_name' => 'Same Name', 'logo' => UploadedFile::fake()->image('a.png')]))->assertSessionHasNoErrors();
        $first = $vendor->fresh()->logo;
        Storage::disk('public')->assertExists($first);

        Vendor::whereKey($vendor->id)->update(['status' => VendorStatus::Rejected->value]);
        $this->actingAs($vendor->user->fresh())->put(route('account.seller.reapply'), $this->shopData(['shop_name' => 'Same Name', 'logo' => UploadedFile::fake()->image('b.png')]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($vendor->fresh()->logo);
    }

    public function test_only_a_rejected_shop_can_reapply(): void
    {
        foreach ([Vendor::factory()->create(), Vendor::factory()->approved()->create(), Vendor::factory()->suspended()->create()] as $vendor) {
            $before = $vendor->status;

            $this->actingAs($vendor->user)->put(route('account.seller.reapply'), $this->shopData())
                ->assertRedirect(route('account.seller.show'))
                ->assertSessionHas('error');

            $this->assertSame($before, $vendor->fresh()->status);
        }

        // A customer without a shop has nothing to re-apply.
        $this->actingAs(User::factory()->create())->put(route('account.seller.reapply'), $this->shopData())->assertNotFound();
    }

    public function test_the_seller_page_always_uses_the_signed_in_users_own_shop(): void
    {
        $mine = Vendor::factory()->rejected()->create(['shop_name' => 'Mine']);
        $other = Vendor::factory()->rejected()->create(['shop_name' => 'Theirs']);

        $this->actingAs($mine->user)->put(route('account.seller.reapply'), $this->shopData(['shop_name' => 'Mine Again']) + ['vendor_id' => $other->id, 'id' => $other->id]);

        $this->assertSame('Mine Again', $mine->fresh()->shop_name);
        $this->assertSame('Theirs', $other->fresh()->shop_name);
        $this->assertSame(VendorStatus::Rejected, $other->fresh()->status);
    }

    public function test_approved_and_suspended_states_on_the_seller_page(): void
    {
        $approved = Vendor::factory()->approved()->create();
        $this->actingAs($approved->user)->get(route('account.seller.show'))
            ->assertSee("You're a seller", false)
            ->assertSee(route('vendor.dashboard'), false);

        $suspended = Vendor::factory()->approved()->create();
        app(VendorStatusService::class)->suspend($suspended, 'Repeated complaints.');

        $this->actingAs($suspended->user->fresh())->get(route('account.seller.show'))
            ->assertSee('Shop suspended')
            ->assertSee('Repeated complaints.');
    }

    /* ---------- Status workflow (single source of truth) ---------- */

    public function test_allowed_transitions_are_defined_on_the_enum(): void
    {
        $allowed = [
            'pending' => ['approved', 'rejected'],
            'rejected' => ['pending'],
            'approved' => ['suspended'],
            'suspended' => ['approved'],
        ];

        foreach (VendorStatus::cases() as $from) {
            foreach (VendorStatus::cases() as $to) {
                $this->assertSame(
                    in_array($to->value, $allowed[$from->value], true),
                    $from->canTransitionTo($to),
                    "{$from->value} -> {$to->value}",
                );
            }
        }
    }

    public function test_the_service_refuses_invalid_transitions_and_blank_reasons(): void
    {
        $service = app(VendorStatusService::class);

        $pending = Vendor::factory()->create();
        $approved = Vendor::factory()->approved()->create();
        $rejected = Vendor::factory()->rejected()->create();

        foreach ([
            fn () => $service->suspend($pending, 'Reason here.'),
            fn () => $service->reactivate($approved),
            fn () => $service->reject($approved, 'Reason here.'),
            fn () => $service->approve($rejected),
            fn () => $service->reapply($pending),
            fn () => $service->reapply($approved),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Expected the transition to be refused.');
            } catch (DomainException) {
                // expected
            }
        }

        foreach ([fn () => $service->reject($pending, '   '), fn () => $service->suspend($approved, '')] as $attempt) {
            try {
                $attempt();
                $this->fail('Expected a missing reason to be refused.');
            } catch (DomainException $e) {
                $this->assertStringContainsString('reason is required', $e->getMessage());
            }
        }

        $this->assertSame(VendorStatus::Pending, $pending->fresh()->status);
        $this->assertSame(VendorStatus::Approved, $approved->fresh()->status);
        $this->assertSame(0, $pending->statusLogs()->count() + $approved->statusLogs()->count() + $rejected->statusLogs()->count());
    }

    public function test_every_change_writes_a_log_row_and_review_fields(): void
    {
        $admin = Admin::factory()->create();
        $service = app(VendorStatusService::class);
        $vendor = Vendor::factory()->create();

        $service->approve($vendor, $admin);
        $vendor->refresh();
        $this->assertSame($admin->id, $vendor->reviewed_by);
        $this->assertNotNull($vendor->reviewed_at);
        $this->assertNotNull($vendor->approved_at);

        $service->suspend($vendor, 'Chargebacks.', $admin);
        $service->reactivate($vendor, $admin);

        $logs = $vendor->statusLogs()->reorder('id')->get();
        $this->assertSame(
            [['pending', 'approved', null], ['approved', 'suspended', 'Chargebacks.'], ['suspended', 'approved', null]],
            $logs->map(fn ($log) => [$log->from_status->value, $log->to_status->value, $log->reason])->all(),
        );
        $this->assertSame([$admin->id], $logs->pluck('admin_id')->unique()->all());

        $rejected = Vendor::factory()->create();
        $service->reject($rejected, 'Not a real business.', $admin);
        $this->assertSame('Not a real business.', $rejected->fresh()->rejection_reason);
        $this->assertNull($rejected->fresh()->approved_at);
    }

    public function test_status_emails_go_to_the_vendor(): void
    {
        Notification::fake();
        $service = app(VendorStatusService::class);
        $vendor = Vendor::factory()->create();

        $service->approve($vendor);
        Notification::assertSentTo($vendor->user, VendorApprovedNotification::class);

        $service->suspend($vendor, 'Chargebacks.');
        Notification::assertSentTo($vendor->user, VendorSuspendedNotification::class, function ($n) use ($vendor) {
            return str_contains(implode(' ', $n->toMail($vendor->user)->introLines), 'Chargebacks.');
        });

        $service->reactivate($vendor);
        Notification::assertSentTo($vendor->user, VendorReactivatedNotification::class);

        $rejected = Vendor::factory()->create();
        $service->reject($rejected, 'Documents missing.');
        Notification::assertSentTo($rejected->user, VendorRejectedNotification::class, function ($n) use ($rejected) {
            $mail = $n->toMail($rejected->user);

            return str_contains(implode(' ', $mail->introLines), 'Documents missing.') && $mail->actionUrl === route('account.seller.show');
        });
    }

    /* ---------- Admin detail page ---------- */

    public function test_detail_page_shows_owner_shop_history_and_only_allowed_actions(): void
    {
        $creator = Admin::factory()->create(['name' => 'Alice Admin']);
        $vendor = Vendor::factory()->createdByAdmin()->approved()->create([
            'shop_name' => 'Detail Shop', 'address' => '7 Detail Lane', 'created_by' => $creator->id, 'reviewed_by' => $creator->id, 'reviewed_at' => now(),
        ]);
        $vendor->statusLogs()->create(['from_status' => null, 'to_status' => VendorStatus::Approved, 'admin_id' => $creator->id, 'note' => 'Created by admin']);

        $html = $this->admin()->get(route('admin.vendors.show', $vendor))->assertOk()->getContent();

        foreach (['Detail Shop', '7 Detail Lane', $vendor->user->email, $vendor->user->name, 'Admin', 'Alice Admin', 'Created by admin', 'Status history'] as $text) {
            $this->assertStringContainsString($text, $html);
        }

        // Approved: only "suspend".
        $this->assertStringContainsString('data-vendor-action="suspend"', $html);
        foreach (['approve', 'reject', 'reactivate'] as $action) {
            $this->assertStringNotContainsString('data-vendor-action="'.$action.'"', $html);
        }
        $this->assertStringContainsString('data-requires-reason="1"', $html);

        $rejected = Vendor::factory()->rejected()->create();
        $this->admin()->get(route('admin.vendors.show', $rejected))->assertOk()->assertDontSee('data-vendor-action', false)->assertSee('apply again');
    }

    /* ---------- Existing vendors keep working after the migration ---------- */

    public function test_existing_vendors_keep_working_after_the_data_migration(): void
    {
        $legacy = Vendor::factory()->approved()->create(['created_at' => now()->subMonth(), 'applied_at' => null, 'source' => 'application']);
        $legacy->user->forceFill(['role' => 'vendor'])->save();
        $pending = Vendor::factory()->create(['applied_at' => null]);
        $pending->user->forceFill(['role' => 'vendor'])->save();

        $migration = require database_path('migrations/2026_10_16_000003_backfill_vendors_and_normalize_roles.php');
        $migration->up();
        $migration->up(); // safe to run twice

        $this->assertSame('customer', $legacy->user->fresh()->role->value);
        $this->assertSame(1, $legacy->statusLogs()->count());
        $this->assertSame(VendorStatus::Approved, $legacy->statusLogs()->first()->to_status);
        $this->assertNotNull($legacy->fresh()->applied_at);
        $this->assertSame(VendorStatus::Pending, $pending->statusLogs()->first()->to_status);

        // Approved legacy vendor: panel works, login goes to the dashboard. Pending one is locked.
        $this->actingAs($legacy->user->fresh())->get(route('vendor.dashboard'))->assertOk();
        $this->actingAs($legacy->user->fresh())->get(route('account.dashboard'))->assertOk();
        $this->actingAs($pending->user->fresh())->get(route('vendor.dashboard'))->assertRedirect(route('account.seller.show'));
        $this->assertSame(route('vendor.dashboard'), $legacy->user->fresh()->homeRoute());
    }

    /* ---------- Admin navbar bell ---------- */

    public function test_the_admin_bell_lists_unread_notifications_and_opens_them(): void
    {
        $admin = Admin::factory()->create();
        $vendor = Vendor::factory()->create(['shop_name' => 'Bell Shop']);
        $admin->notify(new NewVendorApplication($vendor));
        $notification = $admin->unreadNotifications()->first();

        $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))
            ->assertSee('Bell Shop is waiting for approval.')
            ->assertSee(route('admin.notifications.open', $notification->id), false);

        $this->actingAs($admin, 'admin')->get(route('admin.notifications.open', $notification->id))
            ->assertRedirect(route('admin.vendors.show', $vendor));

        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_an_admin_cannot_open_another_admins_notification(): void
    {
        $other = Admin::factory()->create();
        $other->notify(new NewVendorApplication(Vendor::factory()->create()));
        $notification = $other->notifications()->first();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.notifications.open', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }
}
