{{--
    Shared by create and edit. Expects: $action, $types, optional $attribute (edit), optional $method.
    Value rows (add / remove / reorder, color picker for Color attributes): public/admin-assets/js/attribute-values.js
--}}
@php
    $attribute ??= null;
    $rows = old('values', $attribute ? $attribute->values->map(fn ($v) => [
        'id' => $v->id, 'value' => $v->value, 'color_code' => $v->color_code, 'is_active' => $v->is_active,
    ])->all() : [['id' => null, 'value' => '', 'color_code' => '#000000', 'is_active' => true]]);
    $currentType = old('type', $attribute?->type->value ?? 'select');
@endphp

<form method="POST" action="{{ $action }}" data-attribute-form>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <x-admin.card :title="$attribute ? 'Edit Attribute' : 'New Attribute'">
        <div class="row">
            <div class="col-md-4">
                <x-admin.form.input name="name" label="Name" :value="$attribute?->name" maxlength="100" required placeholder="e.g. Size, Color" />
            </div>
            <div class="col-md-4">
                <x-admin.form.select name="type" label="Type" :options="$types" :selected="$currentType" required data-attribute-type
                                     help="Color attributes show swatches on the product page; others show buttons." />
            </div>
            <div class="col-md-2">
                <x-admin.form.input name="sort_order" type="number" min="0" label="Sort order" :value="$attribute?->sort_order ?? 0" help="Lower numbers first." />
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="d-block">Status</label>
                    <x-admin.switch name="is_active" label="Active" :checked="(bool) old('is_active', $attribute?->is_active ?? true)" />
                </div>
            </div>
        </div>
    </x-admin.card>

    <x-admin.card title="Values">
        <p class="text-muted">The order below is the order shoppers see. A value that variants use cannot be removed; mark it inactive instead.</p>
        @error('values')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

        <div data-value-rows>
            @foreach ($rows as $index => $row)
                @include('admin.attributes.partials.value-row', ['index' => $index, 'row' => $row, 'isColor' => $currentType === 'color'])
            @endforeach
        </div>
        <template data-value-template>
            @include('admin.attributes.partials.value-row', ['index' => '__INDEX__', 'row' => ['id' => null, 'value' => '', 'color_code' => '#000000', 'is_active' => true], 'isColor' => false])
        </template>
        <x-admin.button variant="light" icon="fas fa-plus" data-value-add>Add value</x-admin.button>

        <x-slot:footer>
            <div class="text-right">
                <x-admin.button :href="route('admin.attributes.index')" variant="light">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="fas fa-save">{{ $attribute ? 'Save Changes' : 'Create Attribute' }}</x-admin.button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</form>

@push('scripts')
    <script src="{{ asset('admin-assets/js/attribute-values.js') }}"></script>
@endpush
