/*
 * Font Awesome icon picker (see resources/views/components/admin/icon-picker.blade.php).
 *
 * - One shared modal for every picker on the page.
 * - The icon list (local JSON) is fetched the first time a modal opens, then kept in memory.
 * - The grid is built from that list in batches, and search is debounced, so the page never
 *   holds thousands of icons in its HTML.
 * - Keyboard: the search box is focused on open, Esc closes (Bootstrap modal), icons are buttons.
 */
(function ($) {
    'use strict';

    if (window.__iconPickerBound) {
        return;
    }
    window.__iconPickerBound = true;

    var BATCH = 96;
    var SEARCH_DELAY = 200;

    var icons = null;
    var loading = null;
    var active = null;      // the picker root element that opened the modal
    var query = '';
    var style = 'all';
    var matches = [];
    var shown = 0;
    var timer = null;

    var $modal = $('#icon-picker-modal');
    var grid = $modal.find('[data-icon-grid]')[0];
    var search = $modal.find('[data-icon-search]')[0];
    var count = $modal.find('[data-icon-count]')[0];
    var more = $modal.find('[data-icon-more]')[0];

    if (!$modal.length) {
        return;
    }

    function load(url) {
        if (icons) {
            return Promise.resolve(icons);
        }

        if (!loading) {
            loading = fetch(url, {headers: {Accept: 'application/json'}})
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Could not load the icon list.');
                    }

                    return response.json();
                })
                .then(function (data) {
                    icons = data.icons.map(function (icon) {
                        return {c: icon.c, n: icon.n, l: icon.l, t: icon.t, h: (icon.l + ' ' + icon.s).toLowerCase()};
                    });

                    return icons;
                })
                .catch(function (error) {
                    loading = null;
                    throw error;
                });
        }

        return loading;
    }

    // Lower rank sorts first: exact name, name prefix, name contains, then label / search terms.
    function rank(icon, q) {
        if (icon.n === q) { return 0; }
        if (icon.n.indexOf(q) === 0) { return 1; }
        if (icon.n.indexOf(q) > 0) { return 2; }

        return 3;
    }

    function filter() {
        var q = query.trim().toLowerCase().replace(/^fa-/, '');

        matches = icons.filter(function (icon) {
            return (style === 'all' || icon.t === style) && (q === '' || icon.n.indexOf(q) !== -1 || icon.h.indexOf(q) !== -1);
        });

        if (q !== '') {
            matches.sort(function (a, b) { return rank(a, q) - rank(b, q) || (a.n < b.n ? -1 : 1); });
        }
    }

    function currentValue() {
        return active ? active.querySelector('[data-icon-value]').value : '';
    }

    function renderBatch() {
        var selected = currentValue();
        var fragment = document.createDocumentFragment();
        var end = Math.min(shown + BATCH, matches.length);

        for (var i = shown; i < end; i++) {
            var icon = matches[i];
            var col = document.createElement('div');
            var button = document.createElement('button');
            var glyph = document.createElement('i');
            var name = document.createElement('small');

            col.className = 'col-6 col-sm-4 col-md-3 mb-2';

            button.type = 'button';
            button.className = 'btn btn-block ' + (icon.c === selected ? 'btn-primary' : 'btn-light');
            button.setAttribute('data-icon-class', icon.c);
            button.setAttribute('aria-pressed', icon.c === selected ? 'true' : 'false');
            button.title = icon.c;

            glyph.className = icon.c + ' fa-lg d-block mb-1';
            name.className = 'd-block text-truncate';
            name.textContent = icon.n; // textContent: names are never interpreted as HTML

            button.appendChild(glyph);
            button.appendChild(name);
            col.appendChild(button);
            fragment.appendChild(col);
        }

        grid.appendChild(fragment);
        shown = end;

        more.hidden = shown >= matches.length;
        count.textContent = matches.length === 0
            ? 'No icons match your search.'
            : 'Showing ' + shown + ' of ' + matches.length + ' icons';
    }

    function render() {
        grid.innerHTML = '';
        shown = 0;
        filter();
        renderBatch();
    }

    function showMessage(message) {
        grid.innerHTML = '';
        more.hidden = true;
        count.textContent = message;
    }

    function select(iconClass) {
        var value = active.querySelector('[data-icon-value]');
        var display = active.querySelector('[data-icon-display]');
        var preview = active.querySelector('[data-icon-preview]');
        var clear = active.querySelector('[data-icon-clear]');

        value.value = iconClass;
        display.value = iconClass;
        display.classList.remove('is-invalid');
        preview.className = iconClass;
        clear.disabled = iconClass === '';

        if (iconClass === '') {
            preview.className = 'far fa-square text-muted';
        }
    }

    // Open
    $(document).on('click', '[data-icon-open]', function () {
        active = $(this).closest('[data-icon-picker]')[0];

        // Bootstrap 4.1 cancels a show() that arrives while the previous close is still fading its
        // backdrop. If the modal is mid-close, open it as soon as it has finished closing.
        if ($('.modal-backdrop').length && !$modal.hasClass('show')) {
            $modal.one('hidden.bs.modal', function () { $modal.modal('show'); });

            return;
        }

        $modal.modal('show');
    });

    $modal.on('show.bs.modal', function () {
        query = '';
        style = 'all';
        search.value = '';
        $modal.find('[data-icon-style]').removeClass('active').filter('[data-icon-style="all"]').addClass('active');
        showMessage('Loading icons...');
    });

    $modal.on('shown.bs.modal', function () {
        search.focus();

        if (!active) {
            return; // shown without a picker (nothing to fill in)
        }

        load(active.dataset.iconsUrl)
            .then(render)
            .catch(function (error) { showMessage(error.message); });
    });

    // Search (debounced) and style tabs
    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            query = search.value;

            if (icons) {
                render();
            }
        }, SEARCH_DELAY);
    });

    $modal.on('click', '[data-icon-style]', function (event) {
        event.preventDefault();

        style = this.getAttribute('data-icon-style');
        $modal.find('[data-icon-style]').removeClass('active');
        $(this).addClass('active');

        if (icons) {
            render();
        }
    });

    $modal.on('click', '[data-icon-more] button', renderBatch);

    // Choose an icon
    $modal.on('click', '[data-icon-class]', function () {
        select(this.getAttribute('data-icon-class'));
        $modal.modal('hide');
    });

    // Clear
    $(document).on('click', '[data-icon-clear]', function () {
        active = $(this).closest('[data-icon-picker]')[0];
        select('');
    });

    // Give the focus back to the button that opened the modal.
    $modal.on('hidden.bs.modal', function () {
        if (active) {
            var opener = active.querySelector('[data-icon-open]');

            if (opener) {
                opener.focus();
            }
        }
    });
})(jQuery);
