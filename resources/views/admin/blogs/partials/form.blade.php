{{--
    Blog post form, shared by create and edit. Expects: $action, $categories (id => name), optional $blog, optional $method.
    Behaviour: public/admin-assets/js/blog-form.js (slug from title, Summernote with image upload,
    publish date-time picker) and image-preview.js (image preview).
--}}
@php
    $blog ??= null;
    $publishedAt = old('published_at', $blog?->published_at?->copy()->setTimezone(config('marketplace.timezone'))->format('Y-m-d H:i'));
    $tags = old('tags', $blog ? $blog->tags->pluck('name')->implode(', ') : '');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" data-blog-form>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card :title="$blog ? 'Edit Post' : 'New Post'">
                <x-admin.form.input name="title" label="Title" :value="$blog?->title" maxlength="200" required data-slug-source />
                <x-admin.form.input name="slug" label="Slug" :value="$blog?->slug" maxlength="200" data-slug-target
                                    help="The web address of the post. Filled from the title; you can change it." />
                <x-admin.form.textarea name="excerpt" label="Excerpt" :value="$blog?->excerpt" rows="3" maxlength="500"
                                       help="Short summary for search engines and link previews. Leave empty to use the start of the content." />

                @php($contentInvalid = $errors->has('content'))
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea name="content" id="content" @class(['form-control', 'summernote', 'is-invalid' => $contentInvalid])
                              data-upload-url="{{ route('admin.blogs.editor-image') }}">{{ old('content', $blog?->content) }}</textarea>
                    @if ($contentInvalid)<div class="invalid-feedback d-block">{{ $errors->first('content') }}</div>@endif
                </div>
            </x-admin.card>

            <x-admin.card title="SEO">
                <x-admin.form.input name="seo_title" label="SEO title" :value="$blog?->seo_title" maxlength="255"
                                    help="Browser tab and search result title. Empty: the post title." />
                <x-admin.form.textarea name="seo_description" label="SEO description" :value="$blog?->seo_description" rows="3" maxlength="500"
                                       help="Search result text. Empty: the excerpt." />
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Publishing">
                <x-admin.form.select name="status" label="Status" :options="\App\Enums\BlogStatus::options()"
                                     :selected="$blog?->status->value ?? \App\Enums\BlogStatus::Draft->value" />
                <x-admin.form.input name="published_at" label="Publish date & time" :value="$publishedAt" autocomplete="off"
                                    class="blog-datetime" placeholder="YYYY-MM-DD HH:MM"
                                    help="Store time ({{ config('marketplace.timezone') }}). Empty with Published: now. A future time schedules the post." />
                <div class="form-group">
                    <label class="d-block">Home page</label>
                    <x-admin.switch name="show_on_home" label="Show in the home page blog section"
                                    :checked="(bool) old('show_on_home', $blog?->show_on_home ?? true)" />
                </div>
            </x-admin.card>

            <x-admin.card title="Details">
                <x-admin.form.select name="blog_category_id" label="Category" :options="$categories" placeholder="Choose a category"
                                     :selected="$blog?->blog_category_id" required />
                <x-admin.form.input name="tags" label="Tags" :value="$tags" maxlength="500"
                                    help="Separate tags with commas, e.g. fashion, summer, men (up to {{ \App\Services\BlogService::MAX_TAGS }})." />
                <x-admin.form.file name="image" :label="$blog ? 'Image (leave empty to keep the current one)' : 'Image'"
                                   accept=".jpg,.jpeg,.png,.webp" :help="\App\Models\Blog::IMAGE_HINT"
                                   data-preview-target="blog-preview" :required="! $blog" />
                <img id="blog-preview" alt="Blog image preview" width="320" height="168"
                     class="rounded admin-img-cover w-100 {{ $blog ? '' : 'd-none' }}"
                     src="{{ $blog ? ($blog->hasImage() ? $blog->image_url : asset('admin-assets/img/example-image.jpg')) : '' }}">
            </x-admin.card>
        </div>
    </div>

    <div class="text-right mb-4">
        <x-admin.button :href="route('admin.blogs.index')" variant="light">Cancel</x-admin.button>
        @if ($blog)
            <x-admin.button :href="route('admin.blogs.preview', $blog)" variant="info" icon="fas fa-eye" target="_blank" rel="noopener">Preview</x-admin.button>
        @endif
        <x-admin.button type="submit" icon="fas fa-save">{{ $blog ? 'Save Changes' : 'Create Post' }}</x-admin.button>
    </div>
</form>

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/modules/summernote/summernote-bs4.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/modules/bootstrap-daterangepicker/daterangepicker.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('admin-assets/modules/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('admin-assets/modules/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('admin-assets/js/image-preview.js') }}"></script>
    <script src="{{ asset('admin-assets/js/blog-form.js') }}"></script>
@endpush
