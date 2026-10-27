/*
 * Blog post form (admin/blogs/partials/form.blade.php):
 *  - the slug follows the title until the admin edits the slug by hand (the server makes it unique);
 *  - Summernote for the content; inserted images are uploaded (data-upload-url) and placed by URL;
 *  - the publish date-time picker (24 h, store time zone, "YYYY-MM-DD HH:mm"; clearable).
 */
(function ($) {
    'use strict';

    var $form = $('[data-blog-form]');

    if (!$form.length) {
        return;
    }

    // ---- Slug ------------------------------------------------------------------------------------
    var $title = $form.find('[data-slug-source]');
    var $slug = $form.find('[data-slug-target]');
    var slugTouched = $slug.val() !== '';

    function slugify(text) {
        return text.toString().toLowerCase()
            .normalize('NFKD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 200);
    }

    $slug.on('input', function () {
        slugTouched = this.value !== '';
    });

    $title.on('input', function () {
        if (!slugTouched) {
            $slug.val(slugify(this.value));
        }
    });

    // ---- Content editor --------------------------------------------------------------------------
    var $content = $form.find('textarea.summernote');
    var uploadUrl = $content.data('upload-url');

    function errorMessage(xhr) {
        var json = xhr.responseJSON || {};

        if (json.errors) {
            return Object.keys(json.errors).map(function (key) { return json.errors[key][0]; }).join(' ');
        }

        return json.message || 'The image could not be uploaded.';
    }

    function upload(file) {
        var data = new FormData();
        data.append('image', file);

        $.ajax({url: uploadUrl, method: 'POST', data: data, processData: false, contentType: false, dataType: 'json'})
            .done(function (response) {
                $content.summernote('insertImage', response.url, file.name);
            })
            .fail(function (xhr) {
                Swal.fire({position: 'center', icon: 'error', title: 'Upload failed', text: errorMessage(xhr)});
            });
    }

    if ($.fn.summernote) {
        $content.summernote({
            height: 360,
            dialogsInBody: true,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['codeview']]
            ],
            callbacks: {
                onImageUpload: function (files) {
                    Array.prototype.forEach.call(files, upload);
                }
            }
        });
    }

    // ---- Publish date-time -----------------------------------------------------------------------
    var $date = $form.find('.blog-datetime');

    if ($.fn.daterangepicker) {
        $date.daterangepicker({
            singleDatePicker: true,
            timePicker: true,
            timePicker24Hour: true,
            autoUpdateInput: false,
            locale: {format: 'YYYY-MM-DD HH:mm', cancelLabel: 'Clear'}
        });

        $date.on('apply.daterangepicker', function (event, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm'));
        });

        $date.on('cancel.daterangepicker', function () {
            $(this).val('');
        });
    }
})(jQuery);
