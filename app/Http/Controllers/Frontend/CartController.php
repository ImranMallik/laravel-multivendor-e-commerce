<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\AddToCartRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * TODO: the cart itself (session / database cart, totals, checkout) arrives with the cart module.
 * Until then this only VALIDATES the choice (visible product, its own in-stock variant, enough stock) and
 * echoes it back with the unit price; nothing is stored. Cart.add() (public/frontend-assets/js/cart.js)
 * calls it with JSON; the product page form posts to it without JavaScript.
 */
class CartController extends Controller
{
    public function add(AddToCartRequest $request): JsonResponse|RedirectResponse
    {
        $product = $request->product();
        $variant = $request->variant();
        $quantity = $request->integer('quantity');

        $label = sprintf('%d × %s%s', $quantity, $product->name, $variant ? ' ('.$variant->label.')' : '');
        $message = 'Selected '.$label.'. The cart is coming soon.';

        if (! $request->expectsJson()) {
            return back()->with('info', $message);
        }

        // TODO(cart): take the unit price from the price logic again when the item is really added and at checkout.
        $unitPrice = (float) ($variant ?? $product)->current_price;

        return response()->json([
            'added' => false, // the cart does not exist yet
            'message' => $message,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_label' => Product::money($unitPrice * $quantity),
        ]);
    }
}
