<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * The allowed icon list: the same local JSON the admin icon picker shows
 * (public/admin-assets/data/fontawesome-icons.json, built by `php artisan icons:build-fontawesome`).
 * Validation and the picker therefore always agree. The lookup set is cached per file version.
 */
class FontAwesomeIcons
{
    public const PATH = 'admin-assets/data/fontawesome-icons.json';

    /**
     * @return array<string, int> map of "fas fa-tv" => position
     */
    public function classes(): array
    {
        $path = public_path(self::PATH);

        if (! is_file($path)) {
            return [];
        }

        return Cache::rememberForever(
            'fontawesome.icon_classes.'.filemtime($path),
            fn () => collect(json_decode(file_get_contents($path), true)['icons'] ?? [])->pluck('c')->flip()->all(),
        );
    }

    public function exists(string $class): bool
    {
        return isset($this->classes()[$class]);
    }

    public function count(): int
    {
        return count($this->classes());
    }
}
