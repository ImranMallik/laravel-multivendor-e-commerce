<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Local sample brands (re-runnable). Logos are copied from the storefront template's sample images.
 * Run with: php artisan db:seed --class=BrandSeeder
 */
class BrandSeeder extends Seeder
{
    private const BRANDS = ['Northland', 'Aurora', 'Pinecrest', 'Vertex', 'Lumen', 'Harbor'];

    public function run(): void
    {
        $order = 0;

        foreach (self::BRANDS as $index => $name) {
            $logo = 'brands/sample-'.($index + 1).'.jpg';
            $source = public_path('frontend-assets/images/brand_logo_'.($index + 1).'.jpg');

            if (is_file($source)) {
                Storage::disk('public')->put($logo, file_get_contents($source));
            }

            Brand::updateOrCreate(
                ['name' => $name],
                ['logo' => $logo, 'is_featured' => $index < 5, 'is_active' => true, 'sort_order' => ++$order],
            );
        }
    }
}
