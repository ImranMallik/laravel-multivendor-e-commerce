<?php

namespace App\View\Composers;

use App\Services\CategoryMenuService;
use Illuminate\View\View;

class CategoryMenuComposer
{
    public function __construct(private readonly CategoryMenuService $menu) {}

    public function compose(View $view): void
    {
        $view->with('categoryMenu', $this->menu->tree());
    }
}
