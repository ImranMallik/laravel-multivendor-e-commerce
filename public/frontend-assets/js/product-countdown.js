/*
 * Real offer countdowns: every <div class="simply-countdown ..." data-countdown-end="ISO date"> counts
 * down to its own end time (flash sale, hot deals, product page). main.js keeps the template's demo
 * countdown only for elements without data-countdown-end.
 */
(function () {
    'use strict';

    if (typeof simplyCountdown !== 'function') {
        return;
    }

    document.querySelectorAll('[data-countdown-end]').forEach(function (element) {
        var end = new Date(element.dataset.countdownEnd);

        if (isNaN(end.getTime())) {
            return;
        }

        // simplyCountdown takes local date parts; it accepts an element as well as a selector.
        simplyCountdown(element, {
            year: end.getFullYear(),
            month: end.getMonth() + 1,
            day: end.getDate(),
            hours: end.getHours(),
            minutes: end.getMinutes(),
            seconds: end.getSeconds()
        });
    });
})();
