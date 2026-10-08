<?php

use App\Services\FontAwesomeIcons;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Categories may only use icons from the picker's list (Font Awesome Free 5.15.1). Older rows were typed
 * by hand and may use Pro, light or renamed icons, which would fail validation the next time they are
 * edited. Each such icon is mapped to its Free equivalent, or cleared when there is none.
 */
return new class extends Migration
{
    /** Pro / renamed icons => their Free 5.15.1 counterpart. */
    private const RENAMED = [
        'fas fa-chair-office' => 'fas fa-chair',
        'fal fa-chair-office' => 'fas fa-chair',
        'fas fa-home-lg-alt' => 'fas fa-home',
        'fal fa-home-lg-alt' => 'fas fa-home',
        'fal fa-mobile' => 'fas fa-mobile-alt',
        'fas fa-mobile' => 'fas fa-mobile-alt',
        'far fa-camera' => 'fas fa-camera',
        'fal fa-gamepad-alt' => 'fas fa-gamepad',
        'fas fa-gamepad-alt' => 'fas fa-gamepad',
        'fal fa-gift-card' => 'fas fa-gift',
        'fas fa-gift-card' => 'fas fa-gift',
    ];

    public function up(): void
    {
        $icons = app(FontAwesomeIcons::class);

        if ($icons->count() === 0) {
            return; // the icon list has not been generated; nothing to validate against
        }

        DB::table('categories')->whereNotNull('icon')->get(['id', 'icon'])->each(function ($category) use ($icons) {
            if ($icons->exists($category->icon)) {
                return;
            }

            DB::table('categories')->where('id', $category->id)->update(['icon' => $this->replacement($category->icon, $icons)]);
        });
    }

    public function down(): void
    {
        // The original hand-typed values are not kept.
    }

    private function replacement(string $icon, FontAwesomeIcons $icons): ?string
    {
        if (isset(self::RENAMED[$icon]) && $icons->exists(self::RENAMED[$icon])) {
            return self::RENAMED[$icon];
        }

        // Same icon name in another free style (e.g. "fal fa-tshirt" -> "fas fa-tshirt").
        $name = preg_match('/\bfa-([a-z0-9-]+)$/', $icon, $match) ? $match[1] : null;

        foreach (['fas', 'far', 'fab'] as $prefix) {
            if ($name && $icons->exists("{$prefix} fa-{$name}")) {
                return "{$prefix} fa-{$name}";
            }
        }

        return null;
    }
};
