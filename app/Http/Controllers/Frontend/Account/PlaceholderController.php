<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Placeholder account pages until orders and wishlists exist.
 * Replace each method with a dedicated controller (OrderController, WishlistController).
 */
class PlaceholderController extends Controller
{
    public function orders(): View
    {
        return view('frontend.account.orders');
    }

    public function wishlist(): View
    {
        return view('frontend.account.wishlist');
    }
}
