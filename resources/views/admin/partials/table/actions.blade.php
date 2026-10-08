{{-- Edit / Delete cell for CRUD tables. Expects: $editUrl, $deleteUrl, $title, $noun. --}}
<x-admin.button :href="$editUrl" variant="primary" icon="fas fa-edit" size="sm" tooltip="Edit" />
<x-admin.button variant="danger" icon="fas fa-trash" size="sm" tooltip="Delete"
                data-delete-row
                data-url="{{ $deleteUrl }}"
                data-title="{{ $title }}"
                data-noun="{{ $noun }}" />
