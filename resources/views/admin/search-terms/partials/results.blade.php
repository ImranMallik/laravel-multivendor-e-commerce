{{-- Average results of a search term; terms that never found a product get the danger badge. Expects: $average, $neverFound. --}}
{{ number_format($average, 1) }}
@if ($neverFound)
    <x-admin.badge color="danger" class="ml-1">No results</x-admin.badge>
@endif
