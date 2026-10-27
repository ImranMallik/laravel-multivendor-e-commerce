<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\BlogRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SliderRepository;
use App\Services\HomeProductService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(SliderRepository $sliders, ProductRepository $products, BlogRepository $blogs, HomeProductService $weeklyBest): View
    {
        return view('frontend.home.index', [
            'sliders' => $sliders->active(),
            'homeProducts' => $products->homeSections(),
            'blogPosts' => $blogs->homePosts(),
            'weeklyBestRated' => $weeklyBest->weeklyBestRated(),
            'weeklyBestSale' => $weeklyBest->weeklyBestSale(),
        ]);
    }
}
