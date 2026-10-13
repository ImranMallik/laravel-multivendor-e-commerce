/*
 * "Top Categories Of The Month": every tab's products are in one isotope grid (main.js builds it and
 * switches tabs). The grid starts unfiltered, so show only the active tab's products straight away.
 * Loaded after main.js so its ready handler runs after the grid exists.
 */
$(function () {
    'use strict';

    var $grid = $('#wsus__monthly_top .grid');
    var $active = $('#wsus__monthly_top .monthly_top_filter button.active');

    if (!$grid.length || !$active.length || typeof $grid.isotope !== 'function') {
        return;
    }

    $grid.isotope({ filter: $active.attr('data-filter') });
});
