<?php

namespace App\Actions\Brand;

use App\Models\Brand;
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;

class UpdateBrandAction
{
    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * @param  array<string, mixed>  $data  validated fields without the logo
     */
    public function execute(Brand $brand, array $data, ?UploadedFile $logo = null): Brand
    {
        $oldLogo = $brand->logo;

        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($logo) {
            $data['logo'] = $this->images->upload($logo, StoreBrandAction::FOLDER);
        }

        $brand->update($data);

        // Delete the previous file only after the new one is stored and saved.
        if ($logo) {
            $this->images->delete($oldLogo);
        }

        return $brand;
    }
}
