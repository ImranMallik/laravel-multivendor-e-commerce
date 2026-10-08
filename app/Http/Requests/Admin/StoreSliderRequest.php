<?php

namespace App\Http\Requests\Admin;

class StoreSliderRequest extends SliderRequest
{
    protected function imageRequirement(): string
    {
        return 'required';
    }
}
