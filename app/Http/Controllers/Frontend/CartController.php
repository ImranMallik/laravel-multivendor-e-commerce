<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\AddToCartRequest;
use Illuminate\Http\RedirectResponse;

/**
 * TODO: the cart itself (session / database cart, totals, checkout) arrives with the cart module.
 * Until then this validates what the product page sends, including the chosen variant, and echoes it back.
 */
class CartController extends Controller
{
    public function add(AddToCartRequest $request): RedirectResponse
    {
        $product = $request->product();
        $variant = $request->variant();

        return back()->with('info', sprintf(
            'Selected %d x %s%s. The cart is coming soon.',
            $request->integer('quantity'),
            $product->name,
            $variant ? ' ('.$variant->label.')' : '',
        ));
    }
}
