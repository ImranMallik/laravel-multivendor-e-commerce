<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    /**
     * Opens a navbar notification: marks it read and goes to the page it points at.
     * Only the signed-in admin's own notifications are reachable.
     */
    public function __invoke(string $notification): RedirectResponse
    {
        $item = auth('admin')->user()->notifications()->findOrFail($notification);

        $item->markAsRead();

        return redirect($item->data['url'] ?? route('admin.dashboard'));
    }
}
