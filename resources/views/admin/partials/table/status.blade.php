{{-- Status switch cell for CRUD tables. Expects: $model (has is_active), $url (PATCH status route), $label. --}}
<x-admin.switch :checked="$model->is_active"
                data-toggle-status
                data-url="{{ $url }}"
                aria-label="Active: {{ $label }}" />
