/*
 * Storefront search: live suggestions under every form[data-search] (header box and mobile menu, one
 * instance each with its own ids), and the shop page sort select.
 *
 * Suggestions start at 2 characters, 300 ms after the last keystroke. A new request aborts the previous one and
 * an answer that still arrives for an older term is ignored, so results never show out of order. The dropdown
 * is a listbox (arrow keys move, Enter opens the highlighted item or submits the search, Esc closes, a click
 * outside closes). With an empty box, focusing it shows the last 5 submitted searches (localStorage; any
 * storage error simply means no history), each with its own remove button, plus "Clear all".
 *
 * The server sends plain text only (names split into parts flagged "match"); everything is added with
 * textContent, and the only markup created here is the <mark> around matched words.
 */
(function () {
    'use strict';

    var MIN_LENGTH = 2;
    var DEBOUNCE_MS = 300;
    var RECENT_KEY = 'storefront.recent_searches';
    var RECENT_MAX = 5;

    var recent = {
        read: function () {
            try {
                var list = JSON.parse(window.localStorage.getItem(RECENT_KEY) || '[]');

                return Array.isArray(list)
                    ? list.filter(function (item) { return typeof item === 'string' && item !== ''; }).slice(0, RECENT_MAX)
                    : [];
            } catch (e) {
                return [];
            }
        },
        add: function (term) {
            var list = recent.read().filter(function (item) { return item.toLowerCase() !== term.toLowerCase(); });

            list.unshift(term);
            recent.write(list.slice(0, RECENT_MAX));
        },
        remove: function (term) {
            recent.write(recent.read().filter(function (item) { return item !== term; }));
        },
        clear: function () {
            recent.write([]);
        },
        write: function (list) {
            try {
                window.localStorage.setItem(RECENT_KEY, JSON.stringify(list));
            } catch (e) {
                // Private mode or storage disabled: no history.
            }
        }
    };

    function clean(value) {
        return value.replace(/\s+/g, ' ').trim().slice(0, 100);
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (text !== undefined && text !== null) {
            node.textContent = text;
        }

        return node;
    }

    // Name parts from the server: matched words in <mark>, the rest as text.
    function highlighted(parts, fallback) {
        var fragment = document.createDocumentFragment();

        (parts && parts.length ? parts : [{ text: fallback, match: false }]).forEach(function (part) {
            fragment.appendChild(part.match ? el('mark', null, part.text) : document.createTextNode(part.text));
        });

        return fragment;
    }

    function SearchBox(form, index) {
        this.form = form;
        this.input = form.querySelector('input[name="search"]');
        this.url = form.getAttribute('data-suggest-url');
        this.id = 'search-suggest-' + index;
        this.timer = null;
        this.controller = null;
        this.sequence = 0;
        this.active = -1;

        if (!this.input || !this.url) {
            return;
        }

        this.build();
        this.bind();
    }

    SearchBox.prototype.build = function () {
        this.box = el('div', 'wsus__search_suggest');
        this.box.id = this.id;
        this.box.hidden = true;

        this.list = el('ul');
        this.list.id = this.id + '-list';
        this.list.setAttribute('role', 'listbox');
        this.list.setAttribute('aria-label', 'Search suggestions');
        this.box.appendChild(this.list);
        this.form.insertAdjacentElement('afterend', this.box);

        this.spinner = el('span', 'wsus__search_spinner');
        this.spinner.setAttribute('aria-hidden', 'true');
        this.form.appendChild(this.spinner);

        this.input.setAttribute('role', 'combobox');
        this.input.setAttribute('aria-autocomplete', 'list');
        this.input.setAttribute('aria-haspopup', 'listbox');
        this.input.setAttribute('aria-controls', this.list.id);
        this.input.setAttribute('aria-expanded', 'false');
    };

    SearchBox.prototype.bind = function () {
        var self = this;

        this.input.addEventListener('input', function () {
            self.schedule();
        });

        this.input.addEventListener('focus', function () {
            if (clean(self.input.value) === '') {
                self.showRecent();
            } else if (self.list.children.length) {
                self.open();
            }
        });

        this.input.addEventListener('keydown', function (event) {
            self.onKey(event);
        });

        this.form.addEventListener('submit', function (event) {
            var term = clean(self.input.value);

            if (term === '') {
                event.preventDefault();
                self.input.focus();

                return;
            }

            self.input.value = term;
            recent.add(term);
        });

        this.list.addEventListener('mousemove', function (event) {
            var option = event.target.closest('[role="option"]');

            if (option) {
                self.highlight(self.options().indexOf(option));
            }
        });

        // mousedown (not click) so the input keeps focus until the link opens.
        this.list.addEventListener('mousedown', function (event) {
            if (event.target.closest('a, button')) {
                event.preventDefault();
            }
        });

        // The remove/clear buttons stop the click here: the row is re-rendered (the button leaves the page), and
        // the document listener below would otherwise see a click outside and close the dropdown.
        this.list.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-search-remove]');
            var clear = event.target.closest('[data-search-clear]');

            if (remove || clear) {
                event.preventDefault();
                event.stopPropagation();

                if (remove) {
                    recent.remove(remove.getAttribute('data-search-remove'));
                    self.showRecent();
                } else {
                    recent.clear();
                    self.close();
                }

                self.input.focus();

                return;
            }

            var term = clean(self.input.value);

            if (event.target.closest('a') && term !== '') {
                recent.add(term);
            }
        });

        document.addEventListener('click', function (event) {
            if (!self.form.contains(event.target) && !self.box.contains(event.target)) {
                self.close();
            }
        });
    };

    SearchBox.prototype.schedule = function () {
        var self = this;
        var term = clean(this.input.value);

        window.clearTimeout(this.timer);

        if (term.length < MIN_LENGTH) {
            this.abort();
            this.loading(false);

            if (term === '') {
                this.showRecent();
            } else {
                this.close();
            }

            return;
        }

        this.timer = window.setTimeout(function () {
            self.fetch(term);
        }, DEBOUNCE_MS);
    };

    SearchBox.prototype.abort = function () {
        if (this.controller) {
            this.controller.abort();
            this.controller = null;
        }

        this.sequence++;
    };

    SearchBox.prototype.fetch = function (term) {
        var self = this;
        var url = new URL(this.url, window.location.href);
        var category = this.form.querySelector('[name="category"]');

        this.abort();

        var sequence = this.sequence;

        this.controller = window.AbortController ? new AbortController() : null;
        url.searchParams.set('q', term);

        if (category && category.value) {
            url.searchParams.set('category', category.value);
        }

        this.loading(true);

        fetch(url.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: this.controller ? this.controller.signal : undefined
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(function (data) {
                if (sequence === self.sequence) {
                    self.loading(false);
                    self.render(data, term);
                }
            })
            .catch(function (error) {
                if (error.name !== 'AbortError' && sequence === self.sequence) {
                    // Rate limited or offline: no dropdown; submitting the form still searches.
                    self.loading(false);
                    self.close();
                }
            });
    };

    SearchBox.prototype.loading = function (on) {
        this.form.classList.toggle('is-searching', on);
    };

    /* ---------- Rendering ---------- */

    SearchBox.prototype.reset = function () {
        this.list.textContent = '';
        this.active = -1;
        this.input.removeAttribute('aria-activedescendant');
    };

    SearchBox.prototype.heading = function (text, withClear) {
        var item = el('li', 'wsus__search_suggest_heading');

        item.setAttribute('role', 'presentation');
        item.appendChild(el('span', null, text));

        if (withClear) {
            var button = el('button', 'wsus__search_suggest_clear', 'Clear all');

            button.type = 'button';
            button.tabIndex = -1;
            button.setAttribute('data-search-clear', '');
            button.setAttribute('aria-label', 'Clear all recent searches');
            item.appendChild(button);
        }

        this.list.appendChild(item);
    };

    SearchBox.prototype.option = function (href, className, content) {
        var item = el('li', className);
        var link = el('a');

        item.id = this.id + '-option-' + this.list.querySelectorAll('[role="option"]').length;
        item.setAttribute('role', 'option');
        item.setAttribute('aria-selected', 'false');
        link.href = href;
        link.tabIndex = -1;
        link.appendChild(content);
        item.appendChild(link);
        this.list.appendChild(item);

        return item;
    };

    SearchBox.prototype.render = function (data, term) {
        var self = this;

        this.reset();

        (data.products || []).forEach(function (product) {
            var content = document.createDocumentFragment();
            var image = el('img');
            var text = el('span', 'wsus__search_suggest_text');
            var price = el('span', 'wsus__search_suggest_price', product.price);

            image.src = product.image;
            image.alt = '';
            image.width = 50;
            image.height = 50;
            image.loading = 'lazy';

            if (product.old_price) {
                price.appendChild(document.createTextNode(' '));
                price.appendChild(el('del', null, product.old_price));
            }

            text.appendChild(el('span', 'wsus__search_suggest_name')).appendChild(highlighted(product.name_parts, product.name));
            text.appendChild(price);
            content.appendChild(image);
            content.appendChild(text);
            self.option(product.url, 'wsus__search_suggest_product', content);
        });

        if (!(data.products || []).length) {
            var empty = el('li', 'wsus__search_suggest_empty', 'No products found for “' + term + '”');

            empty.setAttribute('role', 'presentation');
            this.list.appendChild(empty);
        }

        this.links('categories', data.categories, function (category) {
            var content = highlighted(category.name_parts, category.name);

            if (category.context) {
                content.appendChild(el('small', null, ' in ' + category.context));
            }

            return content;
        });

        this.links('brands', data.brands, function (brand) {
            return highlighted(brand.name_parts, brand.name);
        });

        this.links('popular categories', data.popular, function (category) {
            return document.createTextNode(category.name);
        });

        var all = document.createDocumentFragment();

        all.appendChild(el('i', 'far fa-search'));
        all.appendChild(el('span', null, 'See all results for “' + term + '”'));
        this.option(data.more_url, 'wsus__search_suggest_all', all);

        this.open();
    };

    SearchBox.prototype.links = function (title, items, content) {
        var self = this;

        if (!items || !items.length) {
            return;
        }

        this.heading(title);
        items.forEach(function (item) {
            // One span around the text: the link is a flex row, and loose text nodes and <mark>s would each
            // become a flex item with a gap between them.
            var label = el('span');

            label.appendChild(content(item));
            self.option(item.url, 'wsus__search_suggest_link', label);
        });
    };

    SearchBox.prototype.showRecent = function () {
        var self = this;
        var terms = recent.read();
        var action = this.form.getAttribute('action');

        this.abort();
        this.reset();

        if (!terms.length) {
            this.close();

            return;
        }

        this.heading('recent searches', true);
        terms.forEach(function (term) {
            var url = new URL(action, window.location.href);
            var content = document.createDocumentFragment();
            var remove = el('button', 'wsus__search_suggest_remove');

            url.searchParams.set('search', term);
            content.appendChild(el('i', 'far fa-history'));
            content.appendChild(el('span', 'wsus__search_suggest_term', term));

            // Next to the link, not inside it (no buttons in links), and kept out of the arrow-key order.
            remove.type = 'button';
            remove.tabIndex = -1;
            remove.setAttribute('data-search-remove', term);
            remove.setAttribute('aria-label', 'Remove ' + term + ' from recent searches');
            remove.appendChild(el('i', 'far fa-times')).setAttribute('aria-hidden', 'true');

            self.option(url.toString(), 'wsus__search_suggest_link wsus__search_suggest_recent', content)
                .appendChild(remove);
        });

        this.open();
    };

    /* ---------- Open / close / keyboard ---------- */

    SearchBox.prototype.open = function () {
        this.box.hidden = false;
        this.input.setAttribute('aria-expanded', 'true');
    };

    SearchBox.prototype.close = function () {
        this.box.hidden = true;
        this.input.setAttribute('aria-expanded', 'false');
        this.highlight(-1);
    };

    SearchBox.prototype.options = function () {
        return Array.prototype.slice.call(this.list.querySelectorAll('[role="option"]'));
    };

    SearchBox.prototype.highlight = function (index) {
        var options = this.options();

        options.forEach(function (option, i) {
            option.classList.toggle('is-active', i === index);
            option.setAttribute('aria-selected', i === index ? 'true' : 'false');
        });

        this.active = index;

        if (index >= 0 && options[index]) {
            this.input.setAttribute('aria-activedescendant', options[index].id);
            options[index].scrollIntoView({ block: 'nearest' });
        } else {
            this.input.removeAttribute('aria-activedescendant');
        }
    };

    SearchBox.prototype.onKey = function (event) {
        var options = this.options();
        var isOpen = !this.box.hidden;

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (!isOpen) {
                if (options.length) {
                    this.open();
                }

                return;
            }

            event.preventDefault();

            var step = event.key === 'ArrowDown' ? 1 : -1;
            var next = this.active + step;

            if (next >= options.length) {
                next = -1;
            } else if (next < -1) {
                next = options.length - 1;
            }

            this.highlight(next);
        } else if (event.key === 'Enter' && isOpen && this.active >= 0 && options[this.active]) {
            event.preventDefault();

            var term = clean(this.input.value);

            if (term !== '') {
                recent.add(term);
            }

            window.location.href = options[this.active].querySelector('a').href;
        } else if (event.key === 'Escape' && isOpen) {
            event.preventDefault();
            this.close();
        } else if (event.key === 'Tab') {
            this.close();
        }
    };

    Array.prototype.forEach.call(document.querySelectorAll('form[data-search]'), function (form, index) {
        new SearchBox(form, index + 1);
    });

    /* ---------- Shop page sort ---------- */

    // The template turns .select_2 into select2, which reports changes through jQuery only.
    function submitSort(event) {
        var form = event.target.closest('form[data-shop-sort]');

        if (form) {
            form.submit();
        }
    }

    if (window.jQuery) {
        window.jQuery(document).on('change', 'form[data-shop-sort] select', submitSort);
    } else {
        document.addEventListener('change', submitSort);
    }
})();
