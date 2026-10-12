/*
 * Product form: variants (Size, Color, ...). Shared by the admin and the vendor panel; it only relies on
 * data-* hooks, so each panel keeps its own markup and classes.
 *
 *   [data-variants-root]            the whole form section (also holds the config attributes)
 *   [data-has-variants]             the "Has variants" checkbox
 *   [data-variants-section]         shown while variants are on
 *   [data-simple-pricing]           the product's own price / stock fields, shown while variants are off
 *   [data-attribute-choice]         checkbox per attribute (data-attribute-id); its values live in
 *                                   [data-attribute-values="id"] with [data-value-choice] checkboxes
 *   [data-variants-generate]        builds every combination of the ticked values
 *   [data-variant-rows] + template[data-variant-template]   one <tr data-variant-row> per variant, __INDEX__ in names
 *   [data-apply-all="price|offer_price|stock_qty"] + [data-apply-value="..."]   fill a column for all rows
 *
 * Existing variants keep their combination (the server refuses a changed one); removing a row soft-deletes
 * the variant when the form is saved. A combination that was removed earlier is restored by generating it again.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-variants-root]');

    if (!root) {
        return;
    }

    var toggle = document.querySelector('[data-has-variants]');
    var section = root.querySelector('[data-variants-section]');
    var simple = document.querySelectorAll('[data-simple-pricing]');
    var rows = root.querySelector('[data-variant-rows]');
    var template = root.querySelector('template[data-variant-template]');
    var message = root.querySelector('[data-variants-message]');
    var nextIndex = parseInt(root.dataset.nextIndex || '0', 10);

    function say(text) {
        if (message) {
            message.textContent = text;
            message.hidden = text === '';
        }
    }

    /* ---------- Has variants switch ---------- */
    function syncMode() {
        var on = toggle.checked;

        section.hidden = !on;
        simple.forEach(function (el) { el.hidden = on; });
    }

    toggle.addEventListener('change', syncMode);
    syncMode();

    /* ---------- Attribute / value choosers ---------- */
    function valuesBox(attributeId) {
        return root.querySelector('[data-attribute-values="' + attributeId + '"]');
    }

    root.querySelectorAll('[data-attribute-choice]').forEach(function (choice) {
        choice.addEventListener('change', function () {
            valuesBox(choice.dataset.attributeId).hidden = !choice.checked;
        });
    });

    // Edit page: tick the attributes and values the existing variants use.
    rows.querySelectorAll('[data-variant-row]').forEach(function (row) {
        var map = JSON.parse(row.dataset.values || '{}');

        Object.keys(map).forEach(function (attributeId) {
            var choice = root.querySelector('[data-attribute-choice][data-attribute-id="' + attributeId + '"]');
            var value = root.querySelector('[data-value-choice][data-attribute-id="' + attributeId + '"][data-value-id="' + map[attributeId] + '"]');

            if (choice) { choice.checked = true; }
            if (value) { value.checked = true; }
        });
    });

    root.querySelectorAll('[data-attribute-choice]').forEach(function (choice) {
        valuesBox(choice.dataset.attributeId).hidden = !choice.checked;
    });

    /* ---------- Generate combinations ---------- */
    function chosenAttributes() {
        var chosen = [];

        root.querySelectorAll('[data-attribute-choice]:checked').forEach(function (choice) {
            var id = choice.dataset.attributeId;
            var values = [];

            valuesBox(id).querySelectorAll('[data-value-choice]:checked').forEach(function (value) {
                values.push({id: value.dataset.valueId, label: value.dataset.valueLabel});
            });

            if (values.length) {
                chosen.push({id: id, name: choice.dataset.attributeName, values: values});
            }
        });

        return chosen;
    }

    function product(chosen) {
        var combos = [[]];

        chosen.forEach(function (attribute) {
            var next = [];

            combos.forEach(function (combo) {
                attribute.values.forEach(function (value) {
                    next.push(combo.concat([{attributeId: attribute.id, valueId: value.id, label: value.label}]));
                });
            });

            combos = next;
        });

        return combos;
    }

    function comboKey(combo) {
        return combo.map(function (part) { return parseInt(part.valueId, 10); }).sort(function (a, b) { return a - b; }).join('-');
    }

    function existingKeys() {
        var keys = {};

        rows.querySelectorAll('[data-variant-row]').forEach(function (row) { keys[row.dataset.combo] = true; });

        return keys;
    }

    function suggestSku(combo) {
        var base = (document.getElementById('sku') || {}).value || 'SKU';
        var tail = combo.map(function (part) { return part.label.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 6); }).join('-');

        return base.trim() + '-' + tail;
    }

    function applyValue(field) {
        var input = root.querySelector('[data-apply-value="' + field + '"]');

        return input ? input.value : '';
    }

    function addRow(combo) {
        var wrapper = document.createElement('table');
        wrapper.innerHTML = '<tbody>' + template.innerHTML.replace(/__INDEX__/g, String(nextIndex)).trim() + '</tbody>';

        var row = wrapper.querySelector('tr');
        var map = {};

        row.dataset.combo = comboKey(combo);
        row.querySelector('[data-variant-label]').textContent = combo.map(function (part) { return part.label; }).join(' / ');
        row.querySelector('[data-variant-sku]').value = suggestSku(combo);
        row.querySelector('[data-variant-price]').value = applyValue('price');
        row.querySelector('[data-variant-offer]').value = applyValue('offer_price');
        row.querySelector('[data-variant-stock]').value = applyValue('stock_qty') || '0';

        combo.forEach(function (part) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'variants[' + nextIndex + '][values][' + part.attributeId + ']';
            hidden.value = part.valueId;
            row.querySelector('[data-variant-values]').appendChild(hidden);
            map[part.attributeId] = part.valueId;
        });

        row.dataset.values = JSON.stringify(map);

        // The first variant of the product becomes the default.
        if (!rows.querySelector('[data-variant-default]:checked')) {
            row.querySelector('[data-variant-default]').checked = true;
        }

        rows.appendChild(row);
        nextIndex++;
    }

    root.querySelector('[data-variants-generate]').addEventListener('click', function () {
        var chosen = chosenAttributes();

        if (!chosen.length) {
            say('Tick at least one attribute and one of its values first.');

            return;
        }

        var keys = existingKeys();
        var added = 0;

        product(chosen).forEach(function (combo) {
            if (!keys[comboKey(combo)]) {
                addRow(combo);
                added++;
            }
        });

        say(added ? added + ' variant(s) added. Fill in the SKU, price and stock, and remove the combinations you do not sell.' : 'Those combinations are already listed.');
    });

    /* ---------- Apply to all, remove ---------- */
    root.querySelectorAll('[data-apply-all]').forEach(function (button) {
        button.addEventListener('click', function () {
            var field = button.dataset.applyAll;
            var value = applyValue(field);

            rows.querySelectorAll('[data-variant-row]').forEach(function (row) {
                row.querySelector('[name$="[' + field + ']"]').value = value;
            });
        });
    });

    rows.addEventListener('click', function (event) {
        var button = event.target.closest('[data-variant-remove]');

        if (!button) {
            return;
        }

        var row = button.closest('[data-variant-row]');
        var wasDefault = row.querySelector('[data-variant-default]').checked;

        row.remove();

        if (wasDefault) {
            var first = rows.querySelector('[data-variant-default]');

            if (first) { first.checked = true; }
        }
    });

    /* ---------- Live image preview per row ---------- */
    rows.addEventListener('change', function (event) {
        var input = event.target;

        if (!input.matches('[data-variant-image]') || !input.files || !input.files[0]) {
            return;
        }

        var preview = input.closest('[data-variant-row]').querySelector('[data-variant-preview]');

        if (preview) {
            preview.src = URL.createObjectURL(input.files[0]);
            preview.hidden = false;
        }
    });
})();
