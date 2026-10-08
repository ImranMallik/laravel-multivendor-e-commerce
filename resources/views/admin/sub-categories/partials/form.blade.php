{{-- Shared by create and edit. Expects: $action, $categories, optional $subCategory (edit), optional $method. --}}
@php($subCategory ??= null)

<form method="POST" action="{{ $action }}">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <x-admin.card :title="$subCategory ? 'Edit Sub Category' : 'New Sub Category'">
        <div class="row">
            <div class="col-md-6">
                <x-admin.form.select name="category_id" label="Category" placeholder="Select a category"
                                     :options="$categories" :selected="$subCategory?->category_id" required />
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="name" label="Name" :value="$subCategory?->name" maxlength="100" required
                                    help="Must be unique inside the chosen category." />
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="sort_order" type="number" min="0" label="Sort order" :value="$subCategory?->sort_order ?? 0"
                                    help="Lower numbers appear first." />
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="d-block">Status</label>
                    <x-admin.switch name="is_active" label="Active (shown on the storefront)"
                                    :checked="(bool) old('is_active', $subCategory?->is_active ?? true)" />
                </div>
            </div>
        </div>

        <x-slot:footer>
            <div class="text-right">
                <x-admin.button :href="route('admin.sub-categories.index')" variant="light">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="fas fa-save">{{ $subCategory ? 'Save Changes' : 'Create Sub Category' }}</x-admin.button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</form>
