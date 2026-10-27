/*
 * Cart (placeholder). EVERY "add to cart" on the storefront ends in Cart.add(), so the real cart is plugged
 * in here, in one place.
 *
 * TODO(cart): the cart itself does not exist yet. For now Cart.add() asks the server to VALIDATE the choice
 * (POST route cart.add with the CSRF token: visible product, its own in-stock variant, enough stock) and
 * tells the shopper the cart is coming soon. Nothing is added anywhere.
 *
 * Hooks (delegated on document, so slider clones and AJAX content work):
 *   [data-cart-add]          a button; product id from data-product-id or the closest [data-product-root]
 *                            (which also gives the chosen variant [data-variant-input] and [data-quantity])
 *   form[data-add-to-cart]   the product page form (it still posts normally when JavaScript is off)
 *   aria-disabled="true"     ignored (out of stock / no valid choice)
 */
(function () {
    'use strict';

    var csrf = document.querySelector('meta[name="csrf-token"]');
    var endpoint = document.querySelector('meta[name="cart-add-url"]');

    function alert(icon, title, text) {
        if (window.Swal) {
            Swal.fire({position: 'center', icon: icon, title: title, text: text, timer: icon === 'error' ? undefined : 2600, timerProgressBar: icon !== 'error', showConfirmButton: icon === 'error'});
        }
    }

    function firstError(json) {
        if (json && json.errors) {
            var key = Object.keys(json.errors)[0];

            return json.errors[key][0];
        }

        return (json && json.message) || 'Something went wrong. Please try again.';
    }

    var Cart = {
        /**
         * @param {number} productId
         * @param {number|null} variantId  required for products with variants
         * @param {number} qty
         * @returns {Promise<object|null>} the server's answer, or null when it was refused
         */
        add: function (productId, variantId, qty) {
            if (!endpoint || !productId) {
                return Promise.resolve(null);
            }

            return fetch(endpoint.content, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({product_id: productId, product_variant_id: variantId || null, quantity: qty || 1})
            })
                .then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (json) {
                        if (!response.ok) {
                            alert('error', 'Could not add', response.status === 419 ? 'Your session expired. Please reload the page.' : firstError(json));

                            return null;
                        }

                        alert('info', 'Cart coming soon', json.message);

                        return json;
                    });
                })
                .catch(function () {
                    alert('error', 'Could not add', 'Please check your connection and try again.');

                    return null;
                });
        }
    };

    window.Cart = Cart;

    // Product id, chosen variant and quantity of a button or form.
    function selection(element) {
        var root = element.closest('[data-product-root]');
        var form = element.closest('form[data-add-to-cart]');
        var scope = root || form;
        var variantInput = scope && scope.querySelector('[data-variant-input]');
        var quantityInput = scope && (scope.querySelector('[data-quantity]') || scope.querySelector('[name="quantity"]'));
        var productId = parseInt(element.dataset.productId || (root && root.dataset.productId) || (form && form.querySelector('[name="product_id"]').value), 10);
        var qty = quantityInput ? Math.max(1, parseInt(quantityInput.value, 10) || 1) : 1;

        return {productId: productId, variantId: variantInput && variantInput.value ? parseInt(variantInput.value, 10) : null, qty: qty};
    }

    function submit(element) {
        if (element.getAttribute('aria-disabled') === 'true' || element.disabled || element.dataset.busy) {
            return;
        }

        var choice = selection(element);
        element.dataset.busy = '1';

        Cart.add(choice.productId, choice.variantId, choice.qty).finally(function () {
            delete element.dataset.busy;
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-cart-add]');

        if (button) {
            event.preventDefault();
            submit(button);
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-add-to-cart]');

        if (form) {
            event.preventDefault();
            submit(form.querySelector('[data-add-button]') || form);
        }
    });
})();
