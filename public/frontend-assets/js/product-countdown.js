/*
 * Real offer countdowns: every <div class="simply-countdown ..." data-countdown-end="ISO date"> counts down
 * to its own end time (flash sell, hot deals, product page, quick view). main.js keeps the template's demo
 * countdown only for elements without data-countdown-end.
 *
 * data-server-now (milliseconds) is the server's clock when the page was made: the countdown corrects for a
 * visitor whose computer clock is wrong. The end time is ISO with its offset, so every time zone sees the
 * same moment.
 *
 * OfferCountdown.init(root)    starts the countdowns inside root (content loaded later, e.g. the quick view)
 * OfferCountdown.destroy(root) stops them (simplyCountdown has no stop function, so its timer is recorded)
 */
(function () {
    'use strict';

    var timers = new WeakMap();

    function start(element) {
        var end = new Date(element.dataset.countdownEnd);

        if (isNaN(end.getTime()) || timers.has(element)) {
            return;
        }

        var serverNow = parseInt(element.dataset.serverNow, 10);
        var skew = isNaN(serverNow) ? 0 : serverNow - Date.now();
        var target = new Date(end.getTime() - skew);
        var ids = [];
        var setInterval = window.setInterval;

        // Record the timer simplyCountdown starts, so destroy() can stop it.
        window.setInterval = function () {
            var id = setInterval.apply(window, arguments);
            ids.push(id);

            return id;
        };

        try {
            // simplyCountdown takes local date parts; it accepts an element as well as a selector.
            simplyCountdown(element, {
                year: target.getFullYear(),
                month: target.getMonth() + 1,
                day: target.getDate(),
                hours: target.getHours(),
                minutes: target.getMinutes(),
                seconds: target.getSeconds()
            });
        } finally {
            window.setInterval = setInterval;
        }

        timers.set(element, ids);
    }

    function init(root) {
        if (typeof simplyCountdown !== 'function') {
            return;
        }

        (root || document).querySelectorAll('[data-countdown-end]').forEach(start);
    }

    function destroy(root) {
        (root || document).querySelectorAll('[data-countdown-end]').forEach(function (element) {
            (timers.get(element) || []).forEach(function (id) { window.clearInterval(id); });
            timers.delete(element);
        });
    }

    window.OfferCountdown = {init: init, destroy: destroy};

    init(document);
})();
