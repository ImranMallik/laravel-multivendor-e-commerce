@extends('admin.layouts.app')

@section('title', $product->name)
@section('page-title', $product->name)

@section('page-actions')
    <x-admin.button :href="route('admin.products.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
    <x-admin.button :href="route('admin.products.edit', $product)" icon="fas fa-edit">Edit</x-admin.button>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <x-admin.card title="Product">
                <x-slot:actions>
                    <x-admin.badge :status="$product->status" />
                    <x-admin.badge :status="$product->approval_status" />
                </x-slot:actions>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <img src="{{ $product->hasThumbnail() ? $product->thumbnail_url : asset('admin-assets/img/example-image.jpg') }}"
                             alt="{{ $product->name }}" width="200" height="200" class="rounded admin-img-cover img-fluid">
                    </div>
                    <div class="col-md-8">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">SKU</dt><dd class="col-sm-8">{{ $product->sku }}</dd>
                            <dt class="col-sm-4">Category</dt>
                            <dd class="col-sm-8">{{ $product->categoryTrail() }}</dd>
                            <dt class="col-sm-4">Brand</dt><dd class="col-sm-8">{{ $product->brand?->name ?? '—' }}</dd>
                            <dt class="col-sm-4">Price</dt><dd class="col-sm-8">@include('admin.products.partials.price', ['product' => $product])</dd>
                            <dt class="col-sm-4">Offer dates</dt>
                            <dd class="col-sm-8">{{ $product->offer_start_date ? $product->offer_start_date->format('d M Y').' – '.$product->offer_end_date?->format('d M Y') : '—' }}</dd>
                            <dt class="col-sm-4">Stock</dt>
                            <dd class="col-sm-8">
                                @if ($product->in_stock){{ $product->stock_qty }}@else<x-admin.badge color="danger">Out of stock</x-admin.badge>@endif
                            </dd>
                            <dt class="col-sm-4">Home sections</dt>
                            <dd class="col-sm-8">
                                @forelse ($product->activeFlags() as $flag)
                                    <x-admin.badge color="info">{{ $flag->label() }}</x-admin.badge>
                                @empty
                                    —
                                @endforelse
                            </dd>
                            <dt class="col-sm-4">Video</dt>
                            <dd class="col-sm-8">@if ($product->video_url)<a href="{{ $product->video_url }}" target="_blank" rel="noopener">{{ $product->video_url }}</a>@else — @endif</dd>
                            <dt class="col-sm-4">Created</dt><dd class="col-sm-8">{{ $product->created_at->format('d M Y H:i') }}</dd>
                        </dl>
                    </div>
                </div>

                @if ($product->short_description)
                    <h6 class="mt-3">Short description</h6>
                    <p>{{ $product->short_description }}</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Gallery">
                @forelse ($product->images as $image)
                    <img src="{{ $image->url }}" alt="Gallery image {{ $loop->iteration }}" width="110" height="110" class="rounded admin-img-cover mr-2 mb-2">
                @empty
                    <p class="text-muted mb-0">No gallery images.</p>
                @endforelse
            </x-admin.card>

            <x-admin.card title="Description">
                {{-- Sanitized when saved (App\Services\HtmlSanitizer). --}}
                @if ($product->long_description)
                    <div>{!! $product->long_description !!}</div>
                @else
                    <p class="text-muted mb-0">No description.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Specifications">
                @if ($product->specifications->isNotEmpty())
                    <dl class="row mb-0">
                        @foreach ($product->specifications as $spec)
                            <dt class="col-sm-4">{{ $spec->key }}</dt><dd class="col-sm-8">{{ $spec->value }}</dd>
                        @endforeach
                    </dl>
                @else
                    <p class="text-muted mb-0">No specifications.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="SEO">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Title</dt><dd class="col-sm-8">{{ $product->seo_title ?: '—' }}</dd>
                    <dt class="col-sm-4">Description</dt><dd class="col-sm-8">{{ $product->seo_description ?: '—' }}</dd>
                </dl>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Approval">
                <p class="mb-2"><x-admin.badge :status="$product->approval_status" /></p>
                @if ($product->rejection_reason)
                    <p><strong>Rejection reason:</strong> {{ $product->rejection_reason }}</p>
                @endif
                @if ($canApprove || $canReject)
                    @include('admin.products.partials.approval-buttons', ['product' => $product])
                @endif
            </x-admin.card>

            <x-admin.card title="Owner">
                @if ($product->vendor)
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Shop</dt>
                        <dd class="col-sm-7"><a href="{{ route('admin.vendors.show', $product->vendor) }}">{{ $product->vendor->shop_name }}</a></dd>
                        <dt class="col-sm-5">Vendor status</dt><dd class="col-sm-7"><x-admin.badge :status="$product->vendor->status" /></dd>
                        <dt class="col-sm-5">Owner</dt><dd class="col-sm-7">{{ $product->vendor->user?->name }}</dd>
                        <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $product->vendor->user?->email }}</dd>
                    </dl>
                @else
                    <p class="mb-0">In-house product</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Approval history">
                <div class="activities">
                    @forelse ($product->approvalLogs as $log)
                        <div class="activity">
                            <div class="activity-icon bg-{{ $log->to_status->color() }} text-white shadow-{{ $log->to_status->color() }}">
                                <i class="fas fa-circle"></i>
                            </div>
                            <div class="activity-detail">
                                <div class="mb-2"><span class="text-job">{{ $log->created_at->format('d M Y H:i') }}</span></div>
                                <p class="mb-1">
                                    @if ($log->from_status)
                                        <x-admin.badge :status="$log->from_status" /> <i class="fas fa-arrow-right text-muted"></i>
                                    @endif
                                    <x-admin.badge :status="$log->to_status" />
                                </p>
                                @if ($log->reason)<p class="mb-1">{{ $log->reason }}</p>@endif
                                <p class="text-muted mb-0">{{ $log->admin?->name ?? 'Vendor / system' }}@if ($log->note) &middot; {{ $log->note }}@endif</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No history yet.</p>
                    @endforelse
                </div>
            </x-admin.card>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/product-actions.js') }}"></script>
@endpush
