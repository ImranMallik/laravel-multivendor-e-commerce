<?php

namespace App\DataTables;

use App\Http\Requests\Admin\SliderTableRequest;
use App\Models\Slider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin sliders table (no page filters, only the table's own search).
 * Column keys here must match the "columns" in public/admin-assets/js/sliders-table.js.
 */
class SliderDataTable
{
    public function json(SliderTableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('image', fn (Slider $slider) => view('admin.sliders.partials.thumbnail', ['slider' => $slider])->render())
            ->editColumn('button_text', fn (Slider $slider) => $slider->button_text ?: '—')
            ->addColumn('is_active', fn (Slider $slider) => view('admin.sliders.partials.status', ['slider' => $slider])->render())
            ->editColumn('created_at', fn (Slider $slider) => $slider->created_at->format('d M Y'))
            ->addColumn('action', fn (Slider $slider) => view('admin.sliders.partials.actions', ['slider' => $slider])->render())
            // A filter callback replaces yajra's automatic per-column search; only the title is searched.
            ->filter(fn ($query) => $query->when(
                filled($request->input('search.value')),
                fn ($q) => $q->where('title', 'like', '%'.$request->input('search.value').'%'),
            ))
            ->rawColumns(['image', 'is_active', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<Slider>
     */
    private function query(SliderTableRequest $request): Builder
    {
        return Slider::query()
            // Without an explicit sort from the table, show slides in banner order.
            ->when(! $request->has('order'), fn (Builder $query) => $query->ordered());
    }
}
