/*
 * Dependent dropdown: <select data-dependent-source="/admin/categories/:id/sub-categories"
 *                             data-dependent-target="#sub_category_id">
 * When the source select changes, the target select is refilled from the JSON endpoint ([{id, name}]).
 * The target's data-selected value is restored on load (edit form and after a validation error);
 * it is also sent as ?include= so a currently-selected but inactive option is still listed.
 */
(function () {
    'use strict';

    function fill(target, placeholder, items, selected) {
        target.innerHTML = '';

        var empty = document.createElement('option');
        empty.value = '';
        empty.textContent = placeholder;
        target.appendChild(empty);

        items.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name; // textContent: names are never interpreted as HTML
            target.appendChild(option);
        });

        if (selected) {
            target.value = selected;
        }

        target.disabled = false;
    }

    function load(source, target, selected) {
        var placeholder = target.dataset.placeholder || 'Select an option';

        if (!source.value) {
            fill(target, placeholder, [], null);
            target.disabled = true;

            return;
        }

        target.disabled = true;

        var url = source.dataset.dependentSource.replace(':id', encodeURIComponent(source.value));

        if (selected) {
            url += '?include=' + encodeURIComponent(selected);
        }

        fetch(url, {headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }

                return response.json();
            })
            .then(function (items) { fill(target, placeholder, items, selected); })
            .catch(function () {
                fill(target, 'Could not load options', [], null);
            });
    }

    document.querySelectorAll('select[data-dependent-source]').forEach(function (source) {
        var target = document.querySelector(source.dataset.dependentTarget);

        if (!target) {
            return;
        }

        source.addEventListener('change', function () {
            load(source, target, null);
        });

        // Initial state (edit page / old input after a validation error).
        load(source, target, target.dataset.selected || null);
    });
})();
