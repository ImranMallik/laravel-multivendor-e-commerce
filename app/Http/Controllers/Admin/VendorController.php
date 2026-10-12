<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ApproveVendorAction;
use App\Actions\Admin\ReactivateVendorAction;
use App\Actions\Admin\RejectVendorAction;
use App\Actions\Admin\SuspendVendorAction;
use App\Actions\Vendor\CreateVendorByAdminAction;
use App\DataTables\VendorDataTable;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectVendorRequest;
use App\Http\Requests\Admin\StoreVendorByAdminRequest;
use App\Http\Requests\Admin\VendorTableRequest;
use App\Models\Vendor;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(): View
    {
        return view('admin.vendors.index', [
            'statuses' => VendorStatus::cases(),
            'counts' => Vendor::statusCounts(),
        ]);
    }

    public function data(VendorTableRequest $request, VendorDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.vendors.create');
    }

    public function store(StoreVendorByAdminRequest $request, CreateVendorByAdminAction $action): RedirectResponse
    {
        try {
            $vendor = $action->execute(
                auth('admin')->user(),
                $request->safe()->except('logo'),
                $request->file('logo'),
            );
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('admin.vendors.show', $vendor)->with('success', 'Vendor created and approved.');
    }

    public function show(Vendor $vendor): View
    {
        return view('admin.vendors.show', [
            'vendor' => $vendor->load(['user', 'createdBy', 'reviewedBy', 'statusLogs.admin']),
        ]);
    }

    public function approve(Vendor $vendor, ApproveVendorAction $action): JsonResponse|RedirectResponse
    {
        return $this->transition(fn () => $action->execute($vendor, auth('admin')->user()), 'Vendor approved.');
    }

    public function reject(RejectVendorRequest $request, Vendor $vendor, RejectVendorAction $action): JsonResponse|RedirectResponse
    {
        return $this->transition(fn () => $action->execute($vendor, $request->validated('reason'), auth('admin')->user()), 'Vendor rejected.');
    }

    public function suspend(RejectVendorRequest $request, Vendor $vendor, SuspendVendorAction $action): JsonResponse|RedirectResponse
    {
        return $this->transition(fn () => $action->execute($vendor, $request->validated('reason'), auth('admin')->user()), 'Vendor suspended.');
    }

    public function reactivate(Vendor $vendor, ReactivateVendorAction $action): JsonResponse|RedirectResponse
    {
        return $this->transition(fn () => $action->execute($vendor, auth('admin')->user()), 'Vendor reactivated.');
    }

    /**
     * Runs a status change and answers JSON for AJAX callers, a redirect otherwise.
     */
    private function transition(callable $change, string $message): JsonResponse|RedirectResponse
    {
        try {
            $vendor = $change();
        } catch (DomainException $e) {
            return request()->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        return request()->expectsJson()
            ? response()->json(['message' => $message, 'status' => $vendor->status->value])
            : back()->with('success', $message);
    }
}
