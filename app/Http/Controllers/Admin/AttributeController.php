<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\AttributeDataTable;
use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttributeRequest;
use App\Http\Requests\Admin\TableRequest;
use App\Models\Attribute;
use App\Services\AttributeService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function index(): View
    {
        return view('admin.attributes.index');
    }

    public function data(TableRequest $request, AttributeDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.attributes.create', ['types' => AttributeType::options()]);
    }

    public function store(AttributeRequest $request, AttributeService $attributes): RedirectResponse
    {
        try {
            $attributes->save($request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute created.');
    }

    public function edit(Attribute $attribute): View
    {
        return view('admin.attributes.edit', [
            'attribute' => $attribute->load('values'),
            'types' => AttributeType::options(),
        ]);
    }

    public function update(AttributeRequest $request, Attribute $attribute, AttributeService $attributes): RedirectResponse
    {
        try {
            $attributes->save($request->validated(), $attribute);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated.');
    }

    public function status(Attribute $attribute, AttributeService $attributes): JsonResponse
    {
        $attribute = $attributes->toggleActive($attribute);

        return response()->json([
            'message' => $attribute->is_active ? 'Attribute activated.' : 'Attribute deactivated.',
            'is_active' => $attribute->is_active,
        ]);
    }

    public function destroy(Request $request, Attribute $attribute, AttributeService $attributes): JsonResponse|RedirectResponse
    {
        try {
            $attributes->delete($attribute);
        } catch (DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Attribute deleted.'])
            : redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted.');
    }
}
