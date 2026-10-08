<?php

namespace App\Http\Requests\Admin;

class UpdateSliderRequest extends SliderRequest
{
    protected function imageRequirement(): string
    {
        return 'nullable';
    }
}
