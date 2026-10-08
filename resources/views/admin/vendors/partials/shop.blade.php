{{-- Shop cell of the vendors table: logo + shop name. Uses the admin avatar when no logo is uploaded. --}}
<a href="{{ route('admin.vendors.show', $vendor) }}" class="d-flex align-items-center text-decoration-none">
    <img src="{{ $vendor->hasLogo() ? $vendor->logo_url : asset('admin-assets/img/avatar/avatar-1.png') }}"
         alt="{{ $vendor->shop_name }}" width="35" height="35" class="rounded-circle mr-2 admin-img-cover">
    <span>{{ $vendor->shop_name }}</span>
</a>
