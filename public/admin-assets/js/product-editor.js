/*
 * Admin product form: Summernote (the admin template's editor) for the long description and a
 * searchable vendor select (Select2). Loaded only on the product create / edit pages.
 */
(function ($) {
    'use strict';

    if ($.fn.summernote) {
        $('textarea.summernote').summernote({
            height: 260,
            dialogsInBody: true,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ]
        });
    }

    if ($.fn.select2) {
        $('select[data-searchable-select]').select2({width: '100%', allowClear: true, placeholder: 'In-house (no vendor)'});
    }
})(jQuery);
