<?php

namespace App\Actions\Vendor;

use App\Enums\UserStatus;
use App\Enums\VendorSource;
use App\Events\VendorCreatedByAdmin;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ImageUploadService;
use App\Services\VendorStatusService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CreateVendorByAdminAction
{
    public function __construct(
        private readonly VendorStatusService $status,
        private readonly ImageUploadService $images,
    ) {}

    /**
     * Super admin creates an approved shop.
     * - New email: a user with a random, unusable password is created. They choose their own password
     *   through the emailed reset link (VendorCreatedByAdmin listener). No password is ever shown or sent.
     * - Existing user without a shop: the shop is attached; their account details stay untouched.
     *
     * @param  array{name: string, email: string, phone?: string|null, shop_name: string, shop_phone?: string|null, address: string}  $data
     *
     * @throws DomainException when the user already has a shop or is blocked
     */
    public function execute(Admin $admin, array $data, ?UploadedFile $logo = null): Vendor
    {
        $data['logo'] = $logo ? $this->images->upload($logo, SubmitVendorApplicationAction::LOGO_FOLDER) : null;
        $userCreated = false;

        try {
            $vendor = DB::transaction(function () use ($admin, $data, &$userCreated) {
                $user = User::with('vendor')->where('email', $data['email'])->lockForUpdate()->first();

                if ($user?->vendor) {
                    throw new DomainException('This user already has a shop.');
                }

                if ($user?->status === UserStatus::Blocked) {
                    throw new DomainException('This account is blocked, so a shop cannot be created for it.');
                }

                if (! $user) {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'password' => Str::random(64), // unusable: nobody knows it; hashed by the model cast
                    ]);
                    $userCreated = true;
                }

                return $this->status->open($user, $data, VendorSource::Admin, $admin);
            });
        } catch (Throwable $e) {
            $this->images->delete($data['logo']);

            throw $e;
        }

        // After the commit, so the listener sees the saved records.
        event(new VendorCreatedByAdmin($vendor, $userCreated));

        return $vendor;
    }
}
