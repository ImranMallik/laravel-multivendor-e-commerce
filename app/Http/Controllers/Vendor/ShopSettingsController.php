<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateShopRequest;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopSettingsController extends Controller
{
    private const LOGO_FOLDER = 'vendors/logos';

    public function __construct(private readonly ImageUploadService $images) {}

    public function edit(): View
    {
        $vendor = auth('web')->user()->vendor;

        $this->authorize('view', $vendor);

        return view('vendor.shop.edit', ['vendor' => $vendor]);
    }

    public function update(UpdateShopRequest $request): RedirectResponse
    {
        // The vendor always comes from the session user, never from the request.
        $vendor = $request->user()->vendor;
        $data = $request->safe()->except('logo');
        $oldLogo = $vendor->logo;

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->images->upload($request->file('logo'), self::LOGO_FOLDER);
        }

        $vendor->update($data);

        // Delete the previous file only after the new one is stored and saved.
        if (isset($data['logo'])) {
            $this->images->delete($oldLogo);
        }

        return back()->with('success', 'Shop settings updated.');
    }
}
