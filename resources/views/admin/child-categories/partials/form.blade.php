{{--
    Shared by create and edit. Expects: $action, $categories, optional $childCategory (edit), optional $method.
    The Sub Category dropdown is filled by AJAX from the chosen category (public/admin-assets/js/dependent-select.js).
--}}
@php($childCategory ??= null)

<form method="POST" action="{{ $action }}">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <x-admin.card :title="$childCategory ? 'Edit Child Category' : 'New Child Category'">
        <div class="row">
            <div class="col-md-6">
                <x-admin.form.select name="category_id" label="Category" placeholder="Select a category"
                                     :options="$categories" :selected="$childCategory?->category_id" required
                                     data-dependent-source="{{ route('admin.categories.sub-categories', ['category' => ':id']) }}"
                                     data-dependent-target="#sub_category_id" />
            </div>
            <div class="col-md-6">
                <x-admin.form.select name="sub_category_id" label="Sub category" placeholder="Select a sub category"
                                     :options="[]" required
                                     data-placeholder="Select a sub category"
                                     data-selected="{{ old('sub_category_id', $childCategory?->sub_category_id) }}"
                                     help="Only the chosen category's sub categories are listed." />
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="name" label="Name" :value="$childCategory?->name" maxlength="100" required
                                    help="Must be unique inside the chosen sub category." />
            </div>
            <div class="col-md-3">
                <x-admin.form.input name="sort_order" type="number" min="0" label="Sort order" :value="$childCategory?->sort_order ?? 0"
                                    help="Lower numbers appear first." />
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="d-block">Status</label>
                    <x-admin.switch name="is_active" label="Active"
                                    :checked="(bool) old('is_active', $childCategory?->is_active ?? true)" />
                </div>
            </div>
        </div>

        <x-slot:footer>
            <div class="text-right">
                <x-admin.button :href="route('admin.child-categories.index')" variant="light">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="fas fa-save">{{ $childCategory ? 'Save Changes' : 'Create Child Category' }}</x-admin.button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</form>

@push('scripts')
    <script src="{{ asset('admin-assets/js/dependent-select.js') }}"></script>
@endpush
