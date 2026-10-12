/*
 * Product page: choose a variant (swatches / buttons). All variant data is embedded as JSON
 * (#variant-data), so a click needs no request. Selecting options updates the price, discount,
 * stock, SKU, main image and the hidden product_variant_id. An option that would lead to a missing
 * or sold-out combination is shown disabled.
 *
 * Markup hooks: .variant-option[data-attribute-id][data-value-id][data-color], [data-price], [data-stock],
 * [data-sku], form[data-add-to-cart], [data-variant-input], [data-add-button], img[data-variant-slide].
 */
(function () {
    'use strict';

    var dataElement = document.getElementById('variant-data');
    var form = document.querySelector('form[data-add-to-cart]');

    if (!dataElement || !form) {
        return;
    }

    var variants = JSON.parse(dataElement.textContent);
    var options = Array.prototype.slice.call(document.querySelectorAll('.variant-option'));
    var priceElement = document.querySelector('[data-price]');
    var stockElement = document.querySelector('[data-stock]');
    var skuElement = document.querySelector('[data-sku]');
    var variantInput = form.querySelector('[data-variant-input]');
    var addButton = form.querySelector('[data-add-button]');
    var quantity = form.querySelector('[name="quantity"]');
    var showingVariantImage = false;

    var selected = {}; // attributeId -> valueId

    // Colored swatches (colors come from data-color so the markup needs no inline styles).
    options.forEach(function (option) {
        if (option.dataset.color) {
            option.style.background = option.dataset.color;
        }

        if (option.classList.contains('is-selected')) {
            selected[option.dataset.attributeId] = parseInt(option.dataset.valueId, 10);
        }
    });

    function find(map) {
        return variants.find(function (variant) {
            return Object.keys(map).every(function (attributeId) { return variant.values.indexOf(map[attributeId]) !== -1; })
                && variant.values.length === Object.keys(map).length;
        });
    }

    function text(element, value) {
        element.textContent = value;
    }

    function render(variant) {
        // Price (with the regular price struck through when discounted).
        priceElement.textContent = variant.current_price_label + ' ';

        if (variant.discount_percent > 0) {
            var del = document.createElement('del');
            del.textContent = variant.price_label;
            priceElement.appendChild(del);
        }

        // Stock
        stockElement.innerHTML = '';
        var badge = document.createElement('span');
        badge.className = 'in_stock';
        text(badge, variant.in_stock ? 'in stock' : 'out of stock');
        stockElement.appendChild(badge);

        if (variant.in_stock) {
            stockElement.appendChild(document.createTextNode(' (' + variant.stock_qty + ' item)'));
        }

        text(skuElement, variant.sku);
        variantInput.value = variant.id;
        addButton.disabled = !variant.in_stock;

        if (quantity) {
            quantity.max = variant.in_stock ? variant.stock_qty : 1;
            if (parseInt(quantity.value, 10) > variant.stock_qty && variant.in_stock) {
                quantity.value = variant.stock_qty;
            }
        }

        showImage(variant);
    }

    // Gallery: variant images are extra slides (li[data-variant-slide]) after the product's own images.
    // Selecting a variant shows its slide (the zoom plugin moves when its thumbnail is hovered), or slide 0 again.
    function showImage(variant) {
        var slides = Array.prototype.slice.call(document.querySelectorAll('#exzoom .exzoom_img_ul li'));
        var index = 0;

        slides.forEach(function (slide, position) {
            if (variant.image && slide.querySelector('[data-variant-slide="' + variant.id + '"]')) {
                index = position;
            }
        });

        if (index === 0 && !showingVariantImage) {
            return; // nothing to change: the product's own first image is already shown
        }

        showingVariantImage = index !== 0;

        if (window.jQuery) {
            jQuery('#exzoom .exzoom_nav span').eq(index).trigger('mouseenter');
        }
    }
    function refresh() {
        options.forEach(function (option) {
            var attributeId = option.dataset.attributeId;
            var valueId = parseInt(option.dataset.valueId, 10);
            var candidate = Object.assign({}, selected);
            candidate[attributeId] = valueId;

            var match = find(candidate);
            var available = !!match && match.in_stock;
            var isSelected = selected[attributeId] === valueId;

            option.classList.toggle('is-selected', isSelected);
            option.classList.toggle('is-disabled', !available && !isSelected);
            option.setAttribute('aria-disabled', !available && !isSelected ? 'true' : 'false');
            option.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        });
    }

    document.addEventListener('click', function (event) {
        var option = event.target.closest('.variant-option');

        if (!option) {
            return;
        }

        event.preventDefault();

        if (option.classList.contains('is-disabled')) {
            return;
        }

        selected[option.dataset.attributeId] = parseInt(option.dataset.valueId, 10);

        var variant = find(selected);

        if (variant) {
            render(variant);
        }

        refresh();
    });

    form.addEventListener('submit', function (event) {
        // The server validates the variant too; this only stops an obviously incomplete choice.
        if (!variantInput.value) {
            event.preventDefault();
        }
    });

    refresh();

    // The zoom gallery is built by main.js on load; show the default variant's own image once it exists.
    window.addEventListener('load', function () {
        var tries = 0;
        var timer = setInterval(function () {
            var variant = find(selected);

            // The zoom plugin builds its thumbnails shortly after load.
            if (document.querySelector('#exzoom .exzoom_nav span') || ++tries > 40) {
                clearInterval(timer);

                if (variant && variant.image) {
                    showImage(variant);
                }
            }
        }, 150);
    });
})();
