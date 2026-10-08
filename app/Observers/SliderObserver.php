<?php

namespace App\Observers;

use App\Models\Slider;
use App\Repositories\SliderRepository;
use App\Services\ImageUploadService;

class SliderObserver
{
    public function __construct(
        private readonly SliderRepository $sliders,
        private readonly ImageUploadService $images,
    ) {}

    public function saved(Slider $slider): void
    {
        $this->sliders->flush();
    }

    public function deleted(Slider $slider): void
    {
        $this->sliders->flush();
    }

    public function restored(Slider $slider): void
    {
        $this->sliders->flush();
    }

    /**
     * A soft delete keeps the image so the slider can be restored; a force delete removes it.
     */
    public function forceDeleted(Slider $slider): void
    {
        $this->images->delete($slider->image);
        $this->sliders->flush();
    }
}
