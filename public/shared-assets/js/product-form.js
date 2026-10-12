/*
 * Product form behaviour, shared by the admin and the vendor panel (relies only on data-* hooks,
 * SweetAlert2's global Swal and the csrf-token meta tag, which both layouts load).
 *
 * - Gallery: live preview of newly chosen images (limited to the free slots), reorder existing images
 *   (their ids are posted as gallery_order[] in display order), remove an existing image over AJAX.
 * - Specifications: add / remove key-value rows from a <template>.
 */
(function () {
    'use strict';

    if (window.__productFormBound) {
        return;
    }
    window.__productFormBound = true;

    var form = document.querySelector('[data-product-form]');

    if (!form) {
        return;
    }

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;
    var existing = form.querySelector('[data-gallery-existing]');
    var input = form.querySelector('[data-gallery-input]');
    var preview = form.querySelector('[data-gallery-new]');
    var counter = form.querySelector('[data-gallery-count]');
    var max = parseInt(form.dataset.galleryMax || '8', 10);

    function existingCount() {
        return existing ? existing.querySelectorAll('[data-gallery-item]').length : 0;
    }

    function freeSlots() {
        return Math.max(0, max - existingCount());
    }

    function updateCounter() {
        if (counter) {
            counter.textContent = existingCount() + ' of ' + max + ' images used, ' + freeSlots() + ' free.';
        }
    }

    function alertError(message) {
        Swal.fire({position: 'center', icon: 'error', title: 'Could not continue', text: message});
    }

    /* ---------- New gallery images: preview ---------- */
    if (input && preview) {
        input.addEventListener('change', function () {
            preview.innerHTML = '';

            var files = Array.prototype.slice.call(input.files || []);

            if (files.length > freeSlots()) {
                alertError('You can add ' + freeSlots() + ' more image(s). Please choose fewer files.');
                input.value = '';

                return;
            }

            files.forEach(function (file) {
                var img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name; // alt text only, never HTML
                img.width = 90;
                img.height = 90;
                img.className = 'rounded mr-2 me-2 mb-2 product-gallery-thumb';
                preview.appendChild(img);
            });
        });
    }

    /* ---------- Existing gallery images: reorder and remove ---------- */
    if (existing) {
        existing.addEventListener('click', function (event) {
            var button = event.target.closest('button');

            if (!button || !existing.contains(button)) {
                return;
            }

            var item = button.closest('[data-gallery-item]');

            if (button.hasAttribute('data-gallery-left') && item.previousElementSibling) {
                existing.insertBefore(item, item.previousElementSibling);
            }

            if (button.hasAttribute('data-gallery-right') && item.nextElementSibling) {
                existing.insertBefore(item.nextElementSibling, item);
            }

            if (button.hasAttribute('data-gallery-remove')) {
                Swal.fire({
                    position: 'center',
                    icon: 'warning',
                    title: 'Remove this image?',
                    text: button.dataset.notice || 'The image is deleted right away.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove',
                    confirmButtonColor: '#fc544b',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    focusCancel: true,
                    showLoaderOnConfirm: true,
                    allowOutsideClick: function () { return !Swal.isLoading(); },
                    preConfirm: function () {
                        return fetch(button.dataset.url, {
                            method: 'DELETE',
                            headers: {'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                        }).then(function (response) {
                            return response.json().catch(function () { return {}; }).then(function (json) {
                                if (!response.ok) {
                                    throw new Error(json.message || 'The image could not be removed.');
                                }

                                return json;
                            });
                        }).catch(function (error) {
                            Swal.showValidationMessage(error.message);

                            return false;
                        });
                    }
                }).then(function (result) {
                    if (!result.isConfirmed || !result.value) {
                        return;
                    }

                    item.remove();
                    updateCounter();
                    Swal.fire({position: 'center', icon: 'success', title: 'Done', text: result.value.message, timer: 1500, timerProgressBar: true, showConfirmButton: false});
                });
            }
        });
    }

    /* ---------- Specifications ---------- */
    var rows = form.querySelector('[data-spec-rows]');
    var template = form.querySelector('template[data-spec-template]');
    var addButton = form.querySelector('[data-spec-add]');
    var nextIndex = rows ? rows.querySelectorAll('[data-spec-row]').length : 0;

    if (rows && template && addButton) {
        addButton.addEventListener('click', function () {
            var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            rows.appendChild(wrapper.firstElementChild);
        });

        rows.addEventListener('click', function (event) {
            var button = event.target.closest('[data-spec-remove]');

            if (button) {
                button.closest('[data-spec-row]').remove();
            }
        });
    }

    updateCounter();
})();
