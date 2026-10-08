<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\SliderRepository;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(SliderRepository $sliders): View
    {
        return view('frontend.home.index', [
            'sliders' => $sliders->active(),
        ]);
    }
}
