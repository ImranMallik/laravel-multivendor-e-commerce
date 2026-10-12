<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Unread notifications for the admin navbar bell.
 */
class AdminNotificationsComposer
{
    public function compose(View $view): void
    {
        $admin = auth('admin')->user();

        $view->with([
            'adminNotifications' => $admin ? $admin->unreadNotifications()->latest()->limit(5)->get() : collect(),
            'adminUnreadCount' => $admin ? $admin->unreadNotifications()->count() : 0,
        ]);
    }
}
