<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ApproveVendorAction;
use App\Actions\Admin\ReactivateVendorAction;
use App\Actions\Admin\RejectVendorAction;
use App\Actions\Admin\SuspendVendorAction;
use App\DataTables\VendorDataTable;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectVendorRequest;
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

    public function show(Vendor $vendor): View
    {
        return view('admin.vendors.show', ['vendor' => $vendor->load('user')]);
    }

    public function approve(Vendor $vendor, ApproveVendorAction $action): JsonResponse|RedirectResponse
    {
        return $this->transition(fn () => $action->execute($vendor), 'Vendor approved.');
    }

    public function reject(RejectVendorRequest $request, Vendor $vendor, RejectVendorAction $action): JsonResponse|RedirectResponse
    {
        return $this->transition(fn () => $action->execute($vendor, $request->validated('reason')), 'Vendor rejected.');
    }

    public function suspend(Vendor $vendor, SuspendVendorAction $action): JsonResponse|RedirectResponse
    {
        return $this->transition(fn () => $action->execute($vendor), 'Vendor suspended.');
    }

    public function reactivate(Vendor $vendor, ReactivateVendorAction $action): JsonResponse|RedirectResponse
    {
        return $this->transition(fn () => $action->execute($vendor), 'Vendor reactivated.');
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
