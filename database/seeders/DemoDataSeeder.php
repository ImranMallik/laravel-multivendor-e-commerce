<?php

namespace Database\Seeders;

use App\Repositories\BrandRepository;
use App\Repositories\ProductRepository;
use App\Services\CategoryMenuService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * DEMO DATA for local development only: realistic products (DemoProductSeeder) and a running flash sale
 * (DemoFlashSaleSeeder). Safe to run more than once. Refuses to run in production.
 *
 * Run with: php artisan db:seed --class=DemoDataSeeder
 * Remove:   delete the products whose SKU starts with DEMO- (and the demo shops Urban Threads / TechNest)
 *           in the admin panel, or reset everything with: php artisan migrate:fresh --seed
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoDataSeeder adds fake products and shops and must not run in production (APP_ENV=production).');
        }

        $this->call([
            DemoProductSeeder::class,
            DemoFlashSaleSeeder::class,
        ]);

        // The observers already clear these on every change; cleared once more so the storefront is fresh.
        app(CategoryMenuService::class)->flush();
        app(ProductRepository::class)->flush();
        app(BrandRepository::class)->flush();
        // TODO(flash-sale): also clear the flash sale cache once the module exists.

        $this->command?->info('Demo data ready. Caches cleared.');
    }
}
