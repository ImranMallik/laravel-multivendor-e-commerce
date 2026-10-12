<?php

namespace App\Actions\Vendor;

use App\Enums\VendorSource;
use App\Events\VendorApplicationSubmitted;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ImageUploadService;
use App\Services\VendorStatusService;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubmitVendorApplicationAction
{
    public const LOGO_FOLDER = 'vendors/logos';

    private const DUPLICATE = 'You already have a shop or a shop application.';

    public function __construct(
        private readonly VendorStatusService $status,
        private readonly ImageUploadService $images,
    ) {}

    /**
     * A signed-in customer applies for a shop (pending until an admin approves it).
     *
     * @param  array{shop_name: string, shop_phone?: string|null, address: string}  $data
     *
     * @throws DomainException when the user already has a shop
     */
    public function execute(User $user, array $data, ?UploadedFile $logo = null): Vendor
    {
        if ($user->hasShop()) {
            throw new DomainException(self::DUPLICATE);
        }

        $data['logo'] = $logo ? $this->images->upload($logo, self::LOGO_FOLDER) : null;

        try {
            $vendor = DB::transaction(function () use ($user, $data) {
                // Checked again inside the transaction; the unique user_id index is the final guard.
                if (Vendor::where('user_id', $user->id)->exists()) {
                    throw new DomainException(self::DUPLICATE);
                }

                return $this->status->open($user, $data, VendorSource::Application);
            });
        } catch (UniqueConstraintViolationException) {
            $this->images->delete($data['logo']);

            throw new DomainException(self::DUPLICATE);
        } catch (Throwable $e) {
            $this->images->delete($data['logo']);

            throw $e;
        }

        // Forget the cached "no shop" so later checks on this user see the new application.
        $user->unsetRelation('vendor');

        event(new VendorApplicationSubmitted($vendor));

        return $vendor;
    }
}
