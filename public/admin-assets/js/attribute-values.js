/*
 * Admin attribute form: add / remove / reorder value rows, and show the color picker only while the
 * attribute type is "Color". Rows come from a <template> (data-value-template) with __INDEX__ in the names.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-attribute-form]');

    if (!form) {
        return;
    }

    var rows = form.querySelector('[data-value-rows]');
    var template = form.querySelector('template[data-value-template]');
    var type = form.querySelector('[data-attribute-type]');
    var nextIndex = rows.querySelectorAll('[data-value-row]').length;

    function isColor() {
        return type.value === 'color';
    }

    function syncColors() {
        rows.querySelectorAll('[data-color-cell]').forEach(function (cell) {
            cell.classList.toggle('d-none', !isColor());
            cell.querySelector('input').disabled = !isColor(); // a disabled input is not posted
        });
    }

    form.querySelector('[data-value-add]').addEventListener('click', function () {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++)).trim();
        rows.appendChild(wrapper.firstElementChild);
        syncColors();
        rows.lastElementChild.querySelector('input[type="text"]').focus();
    });

    rows.addEventListener('click', function (event) {
        var button = event.target.closest('button');

        if (!button) {
            return;
        }

        var row = button.closest('[data-value-row]');

        if (button.hasAttribute('data-value-remove')) {
            row.remove();
        } else if (button.hasAttribute('data-value-up') && row.previousElementSibling) {
            rows.insertBefore(row, row.previousElementSibling);
        } else if (button.hasAttribute('data-value-down') && row.nextElementSibling) {
            rows.insertBefore(row.nextElementSibling, row);
        }
    });

    type.addEventListener('change', syncColors);
    syncColors();
})();
