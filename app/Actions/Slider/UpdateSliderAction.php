<?php

namespace App\Actions\Slider;

use App\Models\Slider;
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;

class UpdateSliderAction
{
    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * @param  array<string, mixed>  $data  validated fields without the image
     */
    public function execute(Slider $slider, array $data, ?UploadedFile $image = null): Slider
    {
        $oldImage = $slider->image;

        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($image) {
            $data['image'] = $this->images->upload($image, StoreSliderAction::FOLDER);
        }

        $slider->update($data);

        // Delete the previous file only after the new one is stored and saved.
        if ($image) {
            $this->images->delete($oldImage);
        }

        return $slider;
    }
}
