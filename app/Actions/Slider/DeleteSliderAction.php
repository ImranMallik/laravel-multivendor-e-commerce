<?php

namespace App\Actions\Slider;

use App\Models\Slider;

class DeleteSliderAction
{
    /**
     * Soft delete: the row and its image are kept so the slider can be restored.
     * The image is removed when the slider is force deleted (see SliderObserver).
     */
    public function execute(Slider $slider): void
    {
        $slider->delete();
    }
}
