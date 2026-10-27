<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * DEMO DATA: one running flash sale with 10 of the demo products.
 *
 * TODO(flash-sale): the Flash Sale module (flash_sales / flash_sale_items, FlashSale models, PriceService)
 * is not built yet. Once it is, this seeder creates (or reuses) the running campaign — starts now, ends in
 * 7 days, shown on home — and adds 10 DEMO- products (simple and variant, from different categories) with
 * 10–40 % discounts (Percent and Fixed, valid against each product's lowest price), 8+ shown on home,
 * sort order 1–10, respecting the no-overlap and no-duplicate rules.
 */
class DemoFlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('flash_sales') || ! Schema::hasTable('flash_sale_items')) {
            $this->command?->warn('Flash sale demo skipped: the Flash Sale module is not installed yet.');

            return;
        }

        $this->command?->warn('Flash sale demo skipped: the flash sale seeding is added together with the Flash Sale module.');
    }
}
