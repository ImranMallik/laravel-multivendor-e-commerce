{{-- Home section flag switches (admin only). Labels and help come from the ProductFlag enum. --}}
@foreach (\App\Enums\ProductFlag::cases() as $flag)
    <div class="text-nowrap" title="{{ $flag->help() }}">
        <x-admin.switch :checked="(bool) $product->{$flag->value}"
                        :label="$flag->label()"
                        data-toggle-status
                        data-field="{{ $flag->value }}"
                        data-url="{{ route('admin.products.flag', [$product, $flag->value]) }}"
                        aria-label="{{ $flag->label() }}: {{ $product->name }}" />
    </div>
@endforeach
