/*
 * Vendor product form: Summernote Lite (no Bootstrap dependency) for the long description.
 * Loaded only on the vendor product create / edit pages.
 */
(function ($) {
    'use strict';

    if (!$.fn.summernote) {
        return;
    }

    $('textarea.summernote').summernote({
        height: 260,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['codeview']]
        ]
    });
})(jQuery);
