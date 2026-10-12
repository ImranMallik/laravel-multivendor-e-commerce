<?php

namespace App\Actions\Vendor;

use App\Models\Vendor;
use App\Services\ImageUploadService;
use App\Services\VendorStatusService;
use Illuminate\Http\UploadedFile;
use Throwable;

class ReapplyVendorAction
{
    public function __construct(
        private readonly VendorStatusService $status,
        private readonly ImageUploadService $images,
    ) {}

    /**
     * A rejected vendor corrects their details and applies again (Rejected -> Pending).
     * Any other status is refused by the status workflow.
     *
     * @param  array{shop_name: string, shop_phone?: string|null, address: string}  $data
     *
     * @throws \DomainException
     */
    public function execute(Vendor $vendor, array $data, ?UploadedFile $logo = null): Vendor
    {
        $oldLogo = $vendor->logo;

        $details = [
            'shop_name' => $data['shop_name'],
            'phone' => $data['shop_phone'] ?? null,
            'address' => $data['address'],
        ];

        $newLogo = $logo ? $this->images->upload($logo, SubmitVendorApplicationAction::LOGO_FOLDER) : null;

        if ($newLogo) {
            $details['logo'] = $newLogo;
        }

        try {
            $this->status->reapply($vendor, $details);
        } catch (Throwable $e) {
            $this->images->delete($newLogo);

            throw $e;
        }

        // The previous file goes only after the new details are saved.
        if ($newLogo) {
            $this->images->delete($oldLogo);
        }

        return $vendor;
    }
}
