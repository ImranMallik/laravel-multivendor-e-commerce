/*
 * Dependent dropdowns, shared by the admin panel and the vendor panel (no template classes used).
 *
 *   <select data-dependent-source="/admin/categories/:id/sub-categories" data-dependent-target="#sub_category_id">
 *
 * When the source select changes, the target select is refilled from the JSON endpoint ([{id, name}]).
 * Levels can be chained (category -> sub category -> child category): refilling a target refreshes
 * the target's own dependents. The target's data-selected value is restored on load (edit form and
 * after a validation error); it is also sent as ?include= so a currently-selected but inactive
 * option is still listed.
 */
(function () {
    'use strict';

    if (window.__dependentSelectBound) {
        return;
    }
    window.__dependentSelectBound = true;

    function refreshed(target, initial) {
        target.dispatchEvent(new CustomEvent('dependent:refresh', {detail: {initial: initial}}));
    }

    function fill(target, placeholder, items, selected, initial) {
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
        refreshed(target, initial);
    }

    function load(source, target, selected, initial) {
        var placeholder = target.dataset.placeholder || 'Select an option';

        if (!source.value) {
            fill(target, placeholder, [], null, initial);
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
            .then(function (items) { fill(target, placeholder, items, selected, initial); })
            .catch(function () {
                fill(target, 'Could not load options', [], null, initial);
            });
    }

    // Mark selects that are filled by a parent, so they wait for the parent instead of loading early.
    document.querySelectorAll('select[data-dependent-source]').forEach(function (source) {
        var target = document.querySelector(source.dataset.dependentTarget);

        if (target) {
            target.setAttribute('data-dependent-target-of', '');
        }
    });

    document.querySelectorAll('select[data-dependent-source]').forEach(function (source) {
        var target = document.querySelector(source.dataset.dependentTarget);

        if (!target) {
            return;
        }

        // The user picked another value: the dependent list starts empty.
        source.addEventListener('change', function () {
            load(source, target, null, false);
        });

        // This select was refilled by its own parent: follow along (keeping the saved value on first load).
        source.addEventListener('dependent:refresh', function (event) {
            var initial = event.detail && event.detail.initial;

            load(source, target, initial ? (target.dataset.selected || null) : null, initial);
        });

        // Top of a chain (its own value is rendered by the server): load straight away.
        if (!source.hasAttribute('data-dependent-target-of')) {
            load(source, target, target.dataset.selected || null, true);
        }
    });
})();
