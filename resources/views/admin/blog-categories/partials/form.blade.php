{{--
    Shared by create and edit. Expects: $action, optional $category (edit), optional $method.
--}}
@php($category ??= null)

<form method="POST" action="{{ $action }}">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <x-admin.card :title="$category ? 'Edit Category' : 'New Category'">
        <div class="row">
            <div class="col-md-6">
                <x-admin.form.input name="name" label="Name" :value="$category?->name" maxlength="100" required
                                    help="The web address (slug) is made from the name." />
            </div>
            <div class="col-md-3">
                <x-admin.form.input name="sort_order" type="number" min="0" label="Sort order" :value="$category?->sort_order ?? 0"
                                    help="Lower numbers appear first." />
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="d-block">Status</label>
                    <x-admin.switch name="is_active" label="Active" :checked="(bool) old('is_active', $category?->is_active ?? true)" />
                </div>
            </div>
        </div>

        <x-slot:footer>
            <div class="text-right">
                <x-admin.button :href="route('admin.blog-categories.index')" variant="light">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="fas fa-save">{{ $category ? 'Save Changes' : 'Create Category' }}</x-admin.button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</form>
