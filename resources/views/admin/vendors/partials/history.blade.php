{{-- Status history timeline (admin template activities). Expects: $logs (VendorStatusLog, newest first). --}}
<div class="activities">
    @forelse ($logs as $log)
        <div class="activity">
            <div class="activity-icon bg-{{ $log->to_status->color() }} text-white shadow-{{ $log->to_status->color() }}">
                <i class="fas fa-circle"></i>
            </div>
            <div class="activity-detail">
                <div class="mb-2">
                    <span class="text-job">{{ $log->created_at->format('d M Y H:i') }}</span>
                </div>
                <p class="mb-1">
                    @if ($log->from_status)
                        <x-admin.badge :status="$log->from_status" /> <i class="fas fa-arrow-right text-muted"></i>
                    @endif
                    <x-admin.badge :status="$log->to_status" />
                </p>
                @if ($log->reason)<p class="mb-1">{{ $log->reason }}</p>@endif
                <p class="text-muted mb-0">
                    {{ $log->admin?->name ?? 'Vendor / system' }}@if ($log->note) &middot; {{ $log->note }}@endif
                </p>
            </div>
        </div>
    @empty
        <p class="text-muted mb-0">No history yet.</p>
    @endforelse
</div>
