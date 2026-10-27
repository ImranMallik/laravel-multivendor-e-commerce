/*
 * Compare (placeholder). Every shuffle button on the storefront ([data-compare-add][data-product-id])
 * calls Compare.add(), so the real compare list is plugged in here, in one place.
 */
(function () {
    'use strict';

    window.Compare = {
        // TODO(compare): add the product to the compare list and update the compare page / counter.
        add: function (productId) {
            if (window.Swal) {
                Swal.fire({position: 'center', icon: 'info', title: 'Compare coming soon', timer: 1800, timerProgressBar: true, showConfirmButton: false});
            }

            return productId;
        }
    };

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-compare-add]');

        if (button) {
            event.preventDefault();
            window.Compare.add(parseInt(button.dataset.productId, 10));
        }
    });
})();
