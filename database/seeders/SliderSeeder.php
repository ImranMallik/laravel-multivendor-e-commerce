<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Local sample data: the template's three slides. The images are copied to the public disk
 * so they behave exactly like uploaded ones. Run with: php artisan db:seed --class=SliderSeeder
 */
class SliderSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            ['new arrivals', "men's fashion", 'start at $99.00'],
            ['new arrivals', "kid's fashion", 'start at $49.00'],
            ['new arrivals', 'winter collection', 'start at $99'],
        ];

        foreach ($slides as $index => [$top, $title, $offer]) {
            $number = $index + 1;
            $source = public_path("frontend-assets/images/slider_{$number}.jpg");

            if (! is_file($source)) {
                $this->command?->warn("Missing template image slider_{$number}.jpg; skipped.");

                continue;
            }

            $path = "sliders/sample-slider-{$number}.jpg";
            Storage::disk('public')->put($path, file_get_contents($source));

            Slider::updateOrCreate(
                ['image' => $path],
                [
                    'top_text' => $top,
                    'title' => $title,
                    'offer_text' => $offer,
                    'button_text' => 'shop now',
                    'button_url' => url('/'),
                    'sort_order' => $number,
                    'is_active' => true,
                ],
            );
        }
    }
}
