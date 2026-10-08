/*
 * Live preview for file inputs: <input type="file" data-preview-target="imgElementId">.
 * Choosing a file shows it in the target <img>; clearing the choice restores the previous state
 * (hidden for a new record, or the current image when editing).
 */
(function () {
    'use strict';

    document.addEventListener('change', function (event) {
        var input = event.target;

        if (!input.matches || !input.matches('input[type="file"][data-preview-target]')) {
            return;
        }

        var preview = document.getElementById(input.dataset.previewTarget);
        var file = input.files && input.files[0];

        if (!preview) {
            return;
        }

        // Remember how the preview looked before the first choice.
        if (preview.dataset.originalSrc === undefined) {
            preview.dataset.originalSrc = preview.getAttribute('src') || '';
            preview.dataset.originalHidden = preview.classList.contains('d-none') ? '1' : '0';
        }

        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
            return;
        }

        if (preview.dataset.originalSrc) {
            preview.src = preview.dataset.originalSrc;
        } else {
            preview.removeAttribute('src');
        }

        preview.classList.toggle('d-none', preview.dataset.originalHidden === '1');
    });
})();
