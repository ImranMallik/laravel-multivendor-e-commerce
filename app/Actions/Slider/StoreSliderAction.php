<?php

namespace App\Actions\Slider;

use App\Models\Slider;
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;

class StoreSliderAction
{
    public const FOLDER = 'sliders';

    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * @param  array<string, mixed>  $data  validated fields without the image
     */
    public function execute(array $data, UploadedFile $image): Slider
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['image'] = $this->images->upload($image, self::FOLDER);

        return Slider::create($data);
    }
}
