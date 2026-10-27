/*
 * Quick view: ONE modal (frontend/partials/quick-view-modal.blade.php) for every product.
 *
 * A click on [data-quick-view][data-product-slug] (card eye, "add to cart" of a product with variants)
 * opens the modal at once with the skeleton loader, loads the product body (HTML inside JSON) and fades it
 * in. Then the template plugins are started on the new content only: the slick gallery, the venobox video,
 * the quantity buttons, the countdown and the variant picker. On close they are stopped and the content is
 * removed, so nothing is ever started twice.
 *
 * A second click on the same product while it loads is ignored; opening another product cancels the older
 * request (and an answer that still arrives for it is ignored). A 404 or network error shows one centered
 * SweetAlert and closes the modal.
 */
(function () {
    'use strict';

    var modalElement = document.getElementById('quick-view-modal');

    if (!modalElement || !window.bootstrap) {
        return;
    }

    var body = modalElement.querySelector('[data-quick-view-body]');
    var skeleton = document.querySelector('template[data-quick-view-skeleton]');
    // The template's Bootstrap 5.0 has no getOrCreateInstance().
    var modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    var current = null; // {slug, controller, trigger}
    var shown = false;

    modalElement.addEventListener('shown.bs.modal', function () {
        shown = true;
        // A slider started while the modal was still opening measured a zero width.
        if (window.jQuery) {
            jQuery(body).find('.modal_slider.slick-initialized').slick('setPosition');
        }
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        shown = false;
        cancel();
        teardown();
        body.innerHTML = '';
    });

    function setLoading(trigger, loading) {
        if (trigger) {
            trigger.classList.toggle('is-loading', loading);
            trigger.setAttribute('aria-busy', loading ? 'true' : 'false');
        }
    }

    function cancel() {
        if (current) {
            current.controller.abort();
            setLoading(current.trigger, false);
            current = null;
        }
    }

    // Stops what was started on the previous content.
    function teardown() {
        if (window.OfferCountdown) {
            OfferCountdown.destroy(body);
        }

        if (window.jQuery) {
            jQuery(body).find('.modal_slider.slick-initialized').slick('unslick');
        }
    }

    function start() {
        var root = body.querySelector('[data-product-root]');

        if (window.jQuery) {
            var $body = jQuery(body);
            var $slider = $body.find('.modal_slider');

            // Same options as the template's main.js.
            if ($slider.children().length > 1) {
                $slider.slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    autoplay: true,
                    autoplaySpeed: 1000,
                    dots: false,
                    arrows: true,
                    nextArrow: '<i class="fas fa-chevron-right nxt_arr"></i>',
                    prevArrow: '<i class="fas fa-chevron-left prv_arr"></i>'
                });
            }

            if ($.fn.venobox) {
                $body.find('.venobox').venobox();
            }

            // The template's quantity buttons (jquery.nice-number.min.js markup: span.spinner > .sub / .add). That
            // file only runs once on page load, and jQuery UI (loaded after it) replaces $.fn.spinner, so the
            // markup is built here; product-variants.js handles the clicks (data-quantity-steps).
            $body.find('.number_area').each(function () {
                var $input = $(this);

                if (!$input.parent().is('.spinner')) {
                    $input.wrap('<span class="spinner" data-quantity-steps></span>');
                    $input.before('<span class="sub" role="button" aria-label="Decrease quantity">-</span>');
                    $input.after('<span class="add" role="button" aria-label="Increase quantity">+</span>');
                }
            });
        }

        if (window.OfferCountdown) {
            OfferCountdown.init(body);
        }

        if (window.VariantPicker && root) {
            VariantPicker.init(root);
        }
    }

    function closeWhenReady() {
        // Bootstrap ignores hide() while the modal is still opening.
        if (shown) {
            modal.hide();
        } else {
            modalElement.addEventListener('shown.bs.modal', function () { modal.hide(); }, {once: true});
        }
    }

    function fail(message) {
        closeWhenReady();

        if (window.Swal) {
            Swal.fire({position: 'center', icon: 'error', title: 'Quick view unavailable', text: message});
        }
    }

    function open(slug, trigger) {
        if (current && current.slug === slug) {
            return; // already loading this product
        }

        cancel();
        teardown();

        var request = {slug: slug, controller: new AbortController(), trigger: trigger};
        current = request;
        setLoading(trigger, true);

        body.innerHTML = skeleton ? skeleton.innerHTML : '';
        modal.show();

        fetch(modalElement.dataset.url.replace('__SLUG__', encodeURIComponent(slug)), {
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            signal: request.controller.signal
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(response.status === 404 ? 'This product is no longer available.' : 'The product could not be loaded. Please try again.');
                }

                return response.json();
            })
            .then(function (json) {
                if (current !== request) {
                    return; // another product was opened meanwhile
                }

                body.innerHTML = '<div class="quick_view_content">' + json.html + '</div>';
                start();
            })
            .catch(function (error) {
                if (error.name === 'AbortError' || current !== request) {
                    return;
                }

                fail(error.message === 'Failed to fetch' ? 'Please check your connection and try again.' : error.message);
            })
            .finally(function () {
                setLoading(trigger, false);

                if (current === request) {
                    current = null;
                }
            });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-quick-view][data-product-slug]');

        if (!trigger) {
            return;
        }

        event.preventDefault();
        open(trigger.dataset.productSlug, trigger);
    });

    window.QuickView = {open: open};
})();
