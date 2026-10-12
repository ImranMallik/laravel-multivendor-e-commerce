<?php

namespace App\View\Composers;

use App\Repositories\BrandRepository;
use Illuminate\View\View;

class BrandSliderComposer
{
    public function __construct(private readonly BrandRepository $brands) {}

    public function compose(View $view): void
    {
        $view->with('brands', $this->brands->featured());
    }
}
