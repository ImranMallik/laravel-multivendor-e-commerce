<?php

namespace App\Actions\Brand;

use App\Models\Brand;
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;

class StoreBrandAction
{
    public const FOLDER = 'brands';

    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * @param  array<string, mixed>  $data  validated fields without the logo
     */
    public function execute(array $data, UploadedFile $logo): Brand
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['logo'] = $this->images->upload($logo, self::FOLDER);

        return Brand::create($data);
    }
}
