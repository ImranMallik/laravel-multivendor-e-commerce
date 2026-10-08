<?php

namespace App\Actions\Slider;

use App\Models\Slider;

class ToggleSliderStatusAction
{
    public function execute(Slider $slider): Slider
    {
        $slider->is_active = ! $slider->is_active;
        $slider->save();

        return $slider;
    }
}
