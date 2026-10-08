<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Slider\DeleteSliderAction;
use App\Actions\Slider\StoreSliderAction;
use App\Actions\Slider\ToggleSliderStatusAction;
use App\Actions\Slider\UpdateSliderAction;
use App\DataTables\SliderDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SliderTableRequest;
use App\Http\Requests\Admin\StoreSliderRequest;
use App\Http\Requests\Admin\UpdateSliderRequest;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SliderController extends Controller
{
    public function index(): View
    {
        return view('admin.sliders.index');
    }

    public function data(SliderTableRequest $request, SliderDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.sliders.create');
    }

    public function store(StoreSliderRequest $request, StoreSliderAction $action): RedirectResponse
    {
        $action->execute($request->safe()->except('image'), $request->file('image'));

        return redirect()->route('admin.sliders.index')->with('success', 'Slider created.');
    }

    public function edit(Slider $slider): View
    {
        return view('admin.sliders.edit', ['slider' => $slider]);
    }

    public function update(UpdateSliderRequest $request, Slider $slider, UpdateSliderAction $action): RedirectResponse
    {
        $action->execute($slider, $request->safe()->except('image'), $request->file('image'));

        return redirect()->route('admin.sliders.index')->with('success', 'Slider updated.');
    }

    public function status(Slider $slider, ToggleSliderStatusAction $action): JsonResponse
    {
        $slider = $action->execute($slider);

        return response()->json([
            'message' => $slider->is_active ? 'Slider activated.' : 'Slider deactivated.',
            'is_active' => $slider->is_active,
        ]);
    }

    public function destroy(Request $request, Slider $slider, DeleteSliderAction $action): JsonResponse|RedirectResponse
    {
        $action->execute($slider);

        return $request->expectsJson()
            ? response()->json(['message' => 'Slider deleted.'])
            : redirect()->route('admin.sliders.index')->with('success', 'Slider deleted.');
    }
}
