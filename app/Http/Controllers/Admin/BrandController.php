<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Brand\DeleteBrandAction;
use App\Actions\Brand\StoreBrandAction;
use App\Actions\Brand\ToggleBrandFlagAction;
use App\Actions\Brand\UpdateBrandAction;
use App\DataTables\BrandDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\TableRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index');
    }

    public function data(TableRequest $request, BrandDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request, StoreBrandAction $action): RedirectResponse
    {
        $action->execute($request->safe()->except('logo'), $request->file('logo'));

        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', ['brand' => $brand]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand, UpdateBrandAction $action): RedirectResponse
    {
        $action->execute($brand, $request->safe()->except('logo'), $request->file('logo'));

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated.');
    }

    public function status(Brand $brand, ToggleBrandFlagAction $action): JsonResponse
    {
        $brand = $action->execute($brand, 'is_active');

        return response()->json([
            'message' => $brand->is_active ? 'Brand activated.' : 'Brand deactivated.',
            'is_active' => $brand->is_active,
        ]);
    }

    public function featured(Brand $brand, ToggleBrandFlagAction $action): JsonResponse
    {
        $brand = $action->execute($brand, 'is_featured');

        return response()->json([
            'message' => $brand->is_featured ? 'Brand marked as featured.' : 'Brand removed from featured.',
            'is_featured' => $brand->is_featured,
        ]);
    }

    public function destroy(Request $request, Brand $brand, DeleteBrandAction $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($brand);
        } catch (DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Brand deleted.'])
            : redirect()->route('admin.brands.index')->with('success', 'Brand deleted.');
    }
}
