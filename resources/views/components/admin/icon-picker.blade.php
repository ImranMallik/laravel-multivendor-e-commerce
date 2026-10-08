{{--
    Visual Font Awesome icon picker (Free 5.15.1, the version the storefront renders).
    <x-admin.icon-picker name="icon" label="Icon" :value="old('icon', $category->icon ?? '')" />

    The hidden input holds the full class string ("fas fa-tv"). The icon list is a local JSON file that is
    fetched only when the modal is first opened (public/admin-assets/js/icon-picker.js).
    One modal is shared by every picker on the page. The matching FA 5.15.1 CSS is loaded once.
--}}
@props(['name' => 'icon', 'label' => 'Icon', 'value' => null, 'help' => null])

@php
    // $errors is shared by the web middleware; fall back to an empty bag when rendered outside a request.
    $errorBag = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $current = (string) $value;
    $invalid = $errorBag->has($name);
@endphp

@pushOnce('styles')
    {{-- The admin template ships Font Awesome 5.5.0; the picker needs 5.15.1 so every icon it offers renders. --}}
    <link rel="stylesheet" href="{{ asset('admin-assets/modules/fontawesome-5.15.1/css/all.min.css') }}">
@endpushOnce

<div class="form-group" data-icon-picker data-icons-url="{{ asset(\App\Services\FontAwesomeIcons::PATH) }}">
    @if ($label)
        <label for="{{ $name }}-display">{{ $label }}</label>
    @endif

    <input type="hidden" name="{{ $name }}" value="{{ $current }}" data-icon-value>

    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text" aria-hidden="true">
                <i data-icon-preview class="{{ $current !== '' ? $current : 'far fa-square text-muted' }}"></i>
            </span>
        </div>
        <input type="text" id="{{ $name }}-display" readonly data-icon-display
               class="form-control {{ $invalid ? 'is-invalid' : '' }}"
               value="{{ $current }}" placeholder="No icon selected">
        <div class="input-group-append">
            <button type="button" class="btn btn-primary" data-icon-open>Choose icon</button>
            <button type="button" class="btn btn-light" data-icon-clear aria-label="Clear icon" title="Clear icon" @disabled($current === '')>
                <i class="fas fa-times"></i>
            </button>
        </div>
        @if ($invalid)
            <div class="invalid-feedback">{{ $errorBag->first($name) }}</div>
        @endif
    </div>

    @if ($help)<small class="form-text text-muted">{{ $help }}</small>@endif
</div>

@pushOnce('modals')
    <div class="modal fade" id="icon-picker-modal" tabindex="-1" role="dialog" aria-labelledby="icon-picker-title" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="icon-picker-title">Choose an icon</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body icon-picker-body">
                    <input type="search" class="form-control mb-3" data-icon-search autocomplete="off"
                           placeholder="Search icons, e.g. tv or phone" aria-label="Search icons">

                    <ul class="nav nav-pills mb-2" data-icon-styles>
                        <li class="nav-item"><a class="nav-link active" href="#" data-icon-style="all">All</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-icon-style="solid">Solid</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-icon-style="regular">Regular</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-icon-style="brands">Brands</a></li>
                    </ul>

                    <p class="text-muted small mb-3" data-icon-count aria-live="polite"></p>

                    <div class="row" data-icon-grid></div>

                    <div class="text-center mt-2" data-icon-more hidden>
                        <button type="button" class="btn btn-light">Show more icons</button>
                    </div>
                </div>
                <div class="modal-footer bg-whitesmoke br">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
@endPushOnce

@pushOnce('scripts')
    <script src="{{ asset('admin-assets/js/icon-picker.js') }}"></script>
@endPushOnce
