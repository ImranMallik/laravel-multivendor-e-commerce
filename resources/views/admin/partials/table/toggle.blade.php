{{-- Generic flag switch cell. Expects: $checked, $url (PATCH route), $field (JSON key the route returns), $label. --}}
<x-admin.switch :checked="$checked"
                data-toggle-status
                data-field="{{ $field }}"
                data-url="{{ $url }}"
                aria-label="{{ $label }}" />
