/*
 * Live preview for file inputs: <input type="file" data-preview-target="imgElementId">.
 */
(function () {
    'use strict';

    if (window.__imagePreviewBound) {
        return;
    }
    window.__imagePreviewBound = true;

    document.addEventListener('change', function (event) {
        var input = event.target;

        if (!input.matches || !input.matches('input[type="file"][data-preview-target]')) {
            return;
        }

        var preview = document.getElementById(input.dataset.previewTarget);
        var file = input.files && input.files[0];

        if (preview && file) {
            preview.src = URL.createObjectURL(file);
        }
    });
})();
