/*
 * Wishlist (placeholder). Every heart button on the storefront ([data-wishlist-toggle][data-product-id])
 * calls Wishlist.toggle(), so the real wishlist is plugged in here, in one place.
 */
(function () {
    'use strict';

    window.Wishlist = {
        // TODO(wishlist): add / remove the product for the signed-in customer and update the header counter.
        toggle: function (productId) {
            if (window.Swal) {
                Swal.fire({position: 'center', icon: 'info', title: 'Wishlist coming soon', timer: 1800, timerProgressBar: true, showConfirmButton: false});
            }

            return productId;
        }
    };

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-wishlist-toggle]');

        if (button) {
            event.preventDefault();
            window.Wishlist.toggle(parseInt(button.dataset.productId, 10));
        }
    });
})();
