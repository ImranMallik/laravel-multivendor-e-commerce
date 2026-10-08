<?php

namespace App\DataTables;

use App\Enums\VendorStatus;
use App\Http\Requests\Admin\VendorTableRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin vendors table.
 * Column keys here must match the "columns" in public/admin-assets/js/vendors-table.js.
 */
class VendorDataTable
{
    public function json(VendorTableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('shop', fn (Vendor $vendor) => view('admin.vendors.partials.shop', ['vendor' => $vendor])->render())
            ->addColumn('owner', fn (Vendor $vendor) => $vendor->user->name)
            ->addColumn('email', fn (Vendor $vendor) => $vendor->user->email)
            ->editColumn('phone', fn (Vendor $vendor) => $vendor->phone ?: ($vendor->user->phone ?: '—'))
            ->editColumn('status', fn (Vendor $vendor) => Blade::render('<x-admin.badge :status="$status" />', ['status' => $vendor->status]))
            ->editColumn('created_at', fn (Vendor $vendor) => $vendor->created_at->format('d M Y'))
            ->addColumn('action', fn (Vendor $vendor) => view('admin.vendors.partials.actions', ['vendor' => $vendor])->render())
            ->orderColumn('shop', 'vendors.shop_name $1')
            // yajra passes the query builder (Eloquent or base), so the callbacks are left untyped.
            ->orderColumn('owner', fn ($query, $dir) => $query->orderBy(
                User::select('name')->whereColumn('users.id', 'vendors.user_id'), $dir
            ))
            ->orderColumn('email', fn ($query, $dir) => $query->orderBy(
                User::select('email')->whereColumn('users.id', 'vendors.user_id'), $dir
            ))
            ->orderColumn('phone', 'vendors.phone $1')
            // A filter callback replaces yajra's automatic per-column search (the default flag).
            ->filter(fn ($query) => $query->search($request->input('search.value')))
            ->rawColumns(['shop', 'status', 'action'])
            ->with('counts', Vendor::statusCounts())
            ->toJson();
    }

    /**
     * @return Builder<Vendor>
     */
    private function query(VendorTableRequest $request): Builder
    {
        $status = VendorStatus::tryFrom((string) $request->validated('status'));

        return Vendor::query()
            ->with('user')
            ->status($status)
            ->registeredBetween($request->validated('date_from'), $request->validated('date_to'))
            ->select('vendors.*');
    }
}
