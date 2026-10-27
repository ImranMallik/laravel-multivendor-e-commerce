/*
 * Variant options, price, stock, SKU, image, quantity and live total for one product "root": the product
 * page buy box and the quick view modal both use it (VariantPicker.init(root)). All variant data is embedded
 * as JSON (script[data-variant-data]), so choosing an option needs no request. An option that would lead to
 * a missing or sold-out combination is disabled; the default variant starts selected.
 *
 * Hooks inside a [data-product-root]: .variant-option[data-attribute-id][data-value-id][data-color],
 * [data-price], [data-stock], [data-sku], [data-variant-input], [data-add-button], [data-quantity],
 * [data-total]; root data-currency, data-unit-price, data-stock-qty (products without variants) and
 * data-gallery = "slick" (quick view slider) or nothing (product page zoom gallery, #exzoom).
 * Events are delegated on document, so content loaded later works after init().
 */
(function () {
    'use strict';

    var states = new WeakMap();

    function money(root, amount) {
        return (root.dataset.currency || '') + Number(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function find(state, map) {
        var keys = Object.keys(map);

        return state.variants.find(function (variant) {
            return variant.values.length === keys.length && keys.every(function (attributeId) {
                return variant.values.indexOf(map[attributeId]) !== -1;
            });
        });
    }

    function setButton(button, available) {
        if (!button) {
            return;
        }

        if (button.tagName === 'BUTTON') {
            button.disabled = !available;
        } else {
            if (available) {
                button.removeAttribute('aria-disabled');
            } else {
                button.setAttribute('aria-disabled', 'true');
            }

            button.textContent = available ? 'add to cart' : 'out of stock';
        }
    }

    // Quantity between 1 and the stock left; the total is price × quantity.
    function updateTotal(root) {
        var state = states.get(root);
        var quantity = root.querySelector('[data-quantity]');
        var total = root.querySelector('[data-total]');

        if (!state || !quantity) {
            return;
        }

        var max = Math.max(1, state.stock);
        var qty = parseInt(quantity.value, 10);

        if (isNaN(qty) || qty < 1) {
            qty = 1;
        } else if (qty > max) {
            qty = max;
        }

        if (String(qty) !== quantity.value && document.activeElement !== quantity) {
            quantity.value = qty;
        }

        quantity.max = max;
        quantity.setAttribute('max', max);

        if (total) {
            total.textContent = money(root, state.price * qty);
        }
    }

    function render(root, variant) {
        var state = states.get(root);
        var price = root.querySelector('[data-price]');
        var stock = root.querySelector('[data-stock]');
        var sku = root.querySelector('[data-sku]');
        var input = root.querySelector('[data-variant-input]');

        if (price) {
            price.textContent = variant.current_price_label + ' ';

            if (variant.discount_percent > 0) {
                var del = document.createElement('del');
                del.textContent = variant.price_label;
                price.appendChild(del);
            }
        }

        if (stock) {
            stock.textContent = '';
            var badge = document.createElement('span');
            badge.className = 'in_stock';
            badge.textContent = variant.in_stock ? 'in stock' : 'out of stock';
            stock.appendChild(badge);

            if (variant.in_stock) {
                stock.appendChild(document.createTextNode(' (' + variant.stock_qty + ' item)'));
            }
        }

        if (sku) {
            sku.textContent = variant.sku;
        }

        if (input) {
            input.value = variant.id;
        }

        state.price = variant.current_price;
        state.stock = variant.in_stock ? variant.stock_qty : 0;
        setButton(root.querySelector('[data-add-button]'), variant.in_stock);
        updateTotal(root);
        showImage(root, variant);
    }

    // The variant's own image is an extra slide (img[data-variant-slide]); without one, the first slide again.
    function showImage(root, variant) {
        var state = states.get(root);

        if (root.dataset.gallery === 'slick') {
            var $slider = window.jQuery ? jQuery(root).find('.modal_slider.slick-initialized') : null;

            if (!$slider || !$slider.length) {
                return;
            }

            var slides = $slider.find('.slick-slide:not(.slick-cloned)');
            var index = slides.index(slides.has('[data-variant-slide="' + variant.id + '"]'));

            if (index > -1 || state.showingVariantImage) {
                $slider.slick('slickPause').slick('slickGoTo', Math.max(index, 0));
            }

            state.showingVariantImage = index > -1;

            return;
        }

        // Product page: the zoom plugin moves when its thumbnail is hovered.
        var thumbs = Array.prototype.slice.call(document.querySelectorAll('#exzoom .exzoom_img_ul li'));
        var position = 0;

        thumbs.forEach(function (slide, i) {
            if (variant.image && slide.querySelector('[data-variant-slide="' + variant.id + '"]')) {
                position = i;
            }
        });

        if (position === 0 && !state.showingVariantImage) {
            return;
        }

        state.showingVariantImage = position !== 0;

        if (window.jQuery) {
            jQuery('#exzoom .exzoom_nav span').eq(position).trigger('mouseenter');
        }
    }

    function refresh(root) {
        var state = states.get(root);

        root.querySelectorAll('.variant-option').forEach(function (option) {
            var attributeId = option.dataset.attributeId;
            var valueId = parseInt(option.dataset.valueId, 10);
            var candidate = Object.assign({}, state.selected);
            candidate[attributeId] = valueId;

            var match = find(state, candidate);
            var available = !!match && match.in_stock;
            var isSelected = state.selected[attributeId] === valueId;

            option.classList.toggle('is-selected', isSelected);
            option.classList.toggle('is-disabled', !available && !isSelected);
            option.setAttribute('aria-disabled', !available && !isSelected ? 'true' : 'false');
            option.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        });
    }

    function init(root) {
        if (!root || states.has(root)) {
            return;
        }

        var json = root.querySelector('script[data-variant-data], script#variant-data');
        var state = {
            variants: json ? JSON.parse(json.textContent) : [],
            selected: {},
            price: parseFloat(root.dataset.unitPrice) || 0,
            stock: parseInt(root.dataset.stockQty, 10) || 0,
            showingVariantImage: false
        };

        states.set(root, state);

        root.querySelectorAll('.variant-option').forEach(function (option) {
            // Colored swatches (colors come from data-color so the markup needs no inline styles).
            if (option.dataset.color) {
                option.style.background = option.dataset.color;
            }

            if (option.classList.contains('is-selected')) {
                state.selected[option.dataset.attributeId] = parseInt(option.dataset.valueId, 10);
            }
        });

        if (state.variants.length) {
            var current = find(state, state.selected);

            if (current) {
                state.price = current.current_price;
                state.stock = current.in_stock ? current.stock_qty : 0;
            }

            refresh(root);
        }

        updateTotal(root);
    }

    document.addEventListener('click', function (event) {
        var option = event.target.closest('.variant-option');

        if (option) {
            event.preventDefault();

            var root = option.closest('[data-product-root]');
            var state = root && states.get(root);

            if (!state || option.classList.contains('is-disabled')) {
                return;
            }

            state.selected[option.dataset.attributeId] = parseInt(option.dataset.valueId, 10);

            var variant = find(state, state.selected);

            if (variant) {
                render(root, variant);
            }

            refresh(root);

            return;
        }

        // The template's quantity buttons. On the product page jquery.nice-number changes the value itself; in the
        // quick view (span.spinner[data-quantity-steps], built by quick-view.js) it is done here. Then the total.
        var step = event.target.closest('.spinner .sub, .spinner .add');
        var stepRoot = step && step.closest('[data-product-root]');

        if (!stepRoot) {
            return;
        }

        if (step.closest('[data-quantity-steps]')) {
            var quantity = stepRoot.querySelector('[data-quantity]');
            var value = (parseInt(quantity.value, 10) || 1) + (step.classList.contains('add') ? 1 : -1);

            quantity.value = Math.min(Math.max(value, parseInt(quantity.min, 10) || 1), parseInt(quantity.max, 10) || 1);
        }

        updateTotal(stepRoot);
    });

    ['input', 'change'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            var quantity = event.target.closest && event.target.closest('[data-quantity]');

            if (quantity && quantity.closest('[data-product-root]')) {
                updateTotal(quantity.closest('[data-product-root]'));
            }
        });
    });

    document.addEventListener('focusout', function (event) {
        var quantity = event.target.closest && event.target.closest('[data-quantity]');

        if (quantity && quantity.closest('[data-product-root]')) {
            updateTotal(quantity.closest('[data-product-root]'));
        }
    });

    // The quantity form of the quick view must never submit (Enter in the field).
    document.addEventListener('submit', function (event) {
        if (event.target.matches && event.target.matches('form[data-quantity-form]')) {
            event.preventDefault();
        }
    });

    window.VariantPicker = {init: init};

    document.querySelectorAll('[data-product-root]').forEach(init);

    // Product page: show the default variant's own image once the zoom gallery (built by main.js on load) exists.
    window.addEventListener('load', function () {
        var root = document.querySelector('[data-product-root]:not([data-gallery])');
        var state = root && states.get(root);

        if (!state || !state.variants.length) {
            return;
        }

        var tries = 0;
        var timer = setInterval(function () {
            if (document.querySelector('#exzoom .exzoom_nav span') || ++tries > 40) {
                clearInterval(timer);

                var variant = find(state, state.selected);

                if (variant && variant.image) {
                    showImage(root, variant);
                }
            }
        }, 150);
    });
})();
