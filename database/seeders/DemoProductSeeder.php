<?php

namespace Database\Seeders;

use App\Actions\Vendor\CreateVendorByAdminAction;
use App\Data\ProductData;
use App\Enums\AttributeType;
use App\Enums\ProductStatus;
use App\Enums\VendorStatus;
use App\Events\VendorCreatedByAdmin;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Services\AttributeService;
use App\Services\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DEMO DATA for local development: realistic products in every category, from the house and from approved
 * vendors, about 30 % with variants, with home flags, offers and the template's own product photos.
 *
 * Everything goes through the real services (ProductService, ProductVariantService via ProductService,
 * AttributeService, CreateVendorByAdminAction), so slugs, combination hashes, mirrored prices, approval logs
 * and image paths are exactly what the admin panel would create. Products are matched by SKU (DEMO-...):
 * running the seeder again updates them instead of creating duplicates (their images are not uploaded twice).
 *
 * Run through DemoDataSeeder: php artisan db:seed --class=DemoDataSeeder
 */
class DemoProductSeeder extends Seeder
{
    /** How many demo products to create (the first COUNT rows of PRODUCTS, at most count(PRODUCTS)). */
    public const COUNT = 50;

    public const SKU_PREFIX = 'DEMO-';

    /** Template photos (template/ecommerce-frontend-template/images) per product kind. */
    private const IMAGES = [
        'blazer' => ['blazer_1.jpg', 'blazer_2.jpg'],
        'dress' => ['pro8.jpg', 'pro8_8.jpg'],
        'kids' => ['kids_1.jpg', 'kids_2.jpg'],
        'shoes' => ['pro2.jpg', 'pro2_2.jpg'],
        'monitor' => ['pro3.jpg', 'pro3_3.jpg', 'daily_deals_2.jpg'],
        'headphone' => ['headphone_1.jpg', 'headphone_2.jpg'],
        'speaker' => ['pro11.jpg'],
        'charger' => ['charger_1.jpg', 'charger_2.jpg'],
        'laptop' => ['pro12.jpg', 'pro12_12.jpg', 'daily_deals_4.jpg'],
        'phone' => ['mobile_1.jpg', 'mobile_2.jpg', 'daily_deals_3.jpg'],
        'watch' => ['pro4.jpg', 'pro4_4.jpg'],
        'smartwatch' => ['pro1.jpg', 'daily_deals_1.jpg'],
        'bag' => ['pro9.jpg', 'pro9_9.jpg'],
        'chair' => ['pro0013.jpg', 'pro10.jpg', 'pro10_10.jpg'],
        'thermometer' => ['pro5.jpg', 'pro5_5.jpg'],
        'fridge' => ['pro0012.jpg'],
        'bicycle' => ['pro0011.jpg'],
    ];

    /** Short description and specifications per product kind (the name is prepended to the long description). */
    private const KINDS = [
        'blazer' => ['Tailored fit, soft lining and a sharp two-button front for office and evenings.', ['Fabric' => 'Poly-viscose blend', 'Fit' => 'Slim fit', 'Closure' => 'Two buttons', 'Care' => 'Dry clean only']],
        'dress' => ['Lightweight, breathable fabric with a relaxed fit for all-day comfort.', ['Fabric' => 'Cotton rayon', 'Sleeve' => 'Long sleeve', 'Length' => 'Knee length', 'Care' => 'Machine wash cold']],
        'kids' => ['Party-ready dress with a soft inner lining that keeps little ones comfortable.', ['Fabric' => 'Net with cotton lining', 'Age group' => '4 to 10 years', 'Closure' => 'Back zip', 'Care' => 'Hand wash']],
        'shoes' => ['Cushioned sole and a breathable knit upper for walks, runs and everyday wear.', ['Upper' => 'Knit mesh', 'Sole' => 'EVA cushioned', 'Closure' => 'Lace-up', 'Weight' => '280 g (one shoe)']],
        'monitor' => ['Crisp IPS panel with thin bezels, wide viewing angles and eye-care modes.', ['Panel' => 'IPS', 'Refresh rate' => '75 Hz', 'Ports' => 'HDMI, VGA', 'Warranty' => '3 years']],
        'headphone' => ['Rich bass, soft ear cushions and long battery life for music and calls.', ['Connectivity' => 'Bluetooth 5.3', 'Battery' => 'Up to 40 hours', 'Charging' => 'USB-C', 'Warranty' => '1 year']],
        'speaker' => ['Room-filling 360° sound in a compact, splash-resistant body.', ['Output' => '20 W', 'Battery' => 'Up to 16 hours', 'Water resistance' => 'IPX5', 'Warranty' => '1 year']],
        'charger' => ['Fast, safe charging with built-in protection against overheating and surges.', ['Output' => 'USB-C PD', 'Input' => '100–240 V', 'Cable' => 'Included', 'Warranty' => '6 months']],
        'laptop' => ['Thin and light laptop with a full HD display, fast SSD and all-day battery.', ['Display' => '14" Full HD IPS', 'Processor' => 'Octa-core, up to 4.4 GHz', 'Battery' => 'Up to 10 hours', 'Warranty' => '1 year']],
        'phone' => ['Smooth display, a capable camera and a big battery with fast charging.', ['Display' => '6.6" FHD+ 120 Hz', 'Camera' => '50 MP + 8 MP', 'Battery' => '5000 mAh, 33 W', 'Network' => '5G']],
        'watch' => ['Stainless steel case and bracelet with a scratch-resistant mineral glass.', ['Case' => 'Stainless steel, 42 mm', 'Movement' => 'Quartz', 'Water resistance' => '50 m', 'Warranty' => '2 years']],
        'smartwatch' => ['Tracks steps, heart rate and sleep, with call and message alerts.', ['Display' => '1.7" colour touch', 'Battery' => 'Up to 7 days', 'Sensors' => 'Heart rate, SpO2', 'Water resistance' => 'IP68']],
        'bag' => ['Roomy main compartment, padded laptop sleeve and comfortable shoulder straps.', ['Capacity' => '28 L', 'Material' => 'Water-resistant polyester', 'Laptop sleeve' => 'Up to 15.6"', 'Warranty' => '1 year']],
        'chair' => ['Adjustable height, lumbar support and a sturdy base for long working hours.', ['Frame' => 'Metal', 'Recline' => 'Up to 135°', 'Max load' => '120 kg', 'Warranty' => '2 years']],
        'thermometer' => ['Accurate, no-touch readings in one second with a fever alert.', ['Measurement' => 'Infrared', 'Reading time' => '1 second', 'Memory' => '32 readings', 'Battery' => '2 × AAA']],
        'fridge' => ['Energy-efficient cooling with toughened glass shelves and a large vegetable box.', ['Energy rating' => '3 star', 'Cooling' => 'Direct cool', 'Shelves' => 'Toughened glass', 'Warranty' => '1 year, 10 years compressor']],
        'bicycle' => ['Sturdy steel frame with training wheels and a padded seat for young riders.', ['Frame' => 'Steel', 'Brakes' => 'Front and rear caliper', 'Training wheels' => 'Included', 'Age group' => '4 to 8 years']],
    ];

    /**
     * One row per product: [sku code, name, category slug, sub slug|null, child slug|null, brand, owner, kind,
     * price, offer|null, offer days left|null (dated offer; null = no dates), stock, flags, variants|null].
     * Owners: house | mallik (the existing approved shop) | urban | technest (demo shops).
     * Variants: ['size_color', sizes, colors, offer %] or ['ram_storage', storages, ram step, storage step, offer %, out-of-stock last?].
     */
    private const PRODUCTS = [
        // Fashion (12)
        ['FAS-001', 'Northland Slim Fit Blazer', 'fashion', 'mens', 'shirts', 'Northland', 'urban', 'blazer', 3499, null, null, 0, ['is_featured', 'is_top'], ['size_color', ['S', 'M', 'L'], ['Black', 'Blue'], 10]],
        ['FAS-002', 'Aurora Classic Sky Blue Blazer', 'fashion', 'mens', 'shirts', 'Aurora', 'urban', 'blazer', 2999, null, null, 0, ['is_best'], ['size_color', ['S', 'M', 'L'], ['Blue', 'White'], 0]],
        ['FAS-003', 'Northland Formal Wool Blend Blazer', 'fashion', 'mens', 'shirts', 'Northland', 'house', 'blazer', 4299, 3699, 5, 18, ['is_featured'], null],
        ['FAS-004', 'Aurora Floral Print Midi Dress', 'fashion', 'womens', 'dresses', 'Aurora', 'urban', 'dress', 1499, null, null, 0, ['is_featured', 'is_top'], ['size_color', ['S', 'M', 'L'], ['Red', 'Blue'], 15]],
        ['FAS-005', 'Aurora Embroidered Cotton Dress', 'fashion', 'womens', 'dresses', 'Aurora', 'urban', 'dress', 1299, null, null, 0, [], ['size_color', ['S', 'M', 'L'], ['Blue', 'Green'], 0]],
        ['FAS-006', 'Aurora Casual Long Sleeve Dress', 'fashion', 'womens', 'dresses', 'Aurora', 'mallik', 'dress', 999, 799, null, 25, [], null],
        ['FAS-007', 'Northland Girls Party Gown', 'fashion', 'kids', 'girls', 'Northland', 'urban', 'kids', 1899, null, null, 0, [], ['size_color', ['S', 'M', 'L'], ['Red', 'Black'], 0, true]],
        ['FAS-008', 'Aurora Girls Floral Frock', 'fashion', 'kids', 'girls', 'Aurora', 'house', 'kids', 1199, 999, 4, 14, ['is_top'], null],
        ['FAS-009', 'Aurora Girls Sequin Net Dress', 'fashion', 'kids', 'girls', 'Aurora', 'mallik', 'kids', 1499, null, null, 0, [], null],
        ['FAS-010', 'Northland Running Sneakers', 'fashion', 'mens', null, 'Northland', 'urban', 'shoes', 2499, null, null, 0, ['is_featured', 'is_top', 'is_best'], ['size_color', ['8', '9', '10'], ['Black', 'White'], 0]],
        ['FAS-011', 'Northland Knit Walking Shoes', 'fashion', 'mens', null, 'Northland', 'house', 'shoes', 1999, null, null, 0, [], ['size_color', ['8', '9', '10'], ['Blue', 'White'], 0, true]],
        ['FAS-012', 'Northland Everyday Canvas Sneakers', 'fashion', 'mens', null, 'Northland', 'house', 'shoes', 1299, null, null, 30, [], null],

        // Electronics (12)
        ['ELE-001', 'Lumen 24" Full HD IPS Monitor', 'electronics', 'consumer-electronic', 'televisions', 'Lumen', 'technest', 'monitor', 10999, 8999, 6, 22, ['is_featured', 'is_top'], null],
        ['ELE-002', 'Lumen 27" QHD Ultra-Slim Monitor', 'electronics', 'consumer-electronic', 'televisions', 'Lumen', 'technest', 'monitor', 18999, 16499, null, 12, ['is_best'], null],
        ['ELE-003', 'Vertex 32" 4K Smart Display', 'electronics', 'consumer-electronic', 'televisions', 'Vertex', 'technest', 'monitor', 24999, null, null, 9, ['is_featured', 'is_top'], null],
        ['ELE-004', 'Vertex Wireless Over-Ear Headphones', 'electronics', 'consumer-electronic', 'speakers', 'Vertex', 'technest', 'headphone', 4999, 3499, 3, 40, ['is_featured', 'is_best'], null],
        ['ELE-005', 'Lumen Studio Bass Headphones', 'electronics', 'consumer-electronic', 'speakers', 'Lumen', 'mallik', 'headphone', 2999, null, null, 35, [], null],
        ['ELE-006', 'Vertex SoundCore Bluetooth Speaker', 'electronics', 'consumer-electronic', 'speakers', 'Vertex', 'house', 'speaker', 3499, 2799, null, 28, ['is_featured'], null],
        ['ELE-007', 'Lumen 20W USB-C Fast Charger', 'electronics', 'accessories-parts', 'chargers', 'Lumen', 'technest', 'charger', 1299, 899, null, 120, [], null],
        ['ELE-008', 'Vertex 33W Dual Port Charger', 'electronics', 'accessories-parts', 'chargers', 'Vertex', 'house', 'charger', 1499, null, null, 80, [], null],
        ['ELE-009', 'Lumen Braided USB-C Cable 1.5 m', 'electronics', 'accessories-parts', 'cables', 'Lumen', 'mallik', 'charger', 499, 349, null, 200, [], null],
        ['ELE-010', 'Vertex AirBook 14 Laptop', 'electronics', 'consumer-electronic', null, 'Vertex', 'technest', 'laptop', 45999, null, null, 0, ['is_featured', 'is_top'], ['ram_storage', ['256 GB', '512 GB'], 6000, 4000, 8]],
        ['ELE-011', 'Lumen ProBook 15.6 Laptop', 'electronics', 'consumer-electronic', null, 'Lumen', 'technest', 'laptop', 54999, null, null, 0, ['is_best'], ['ram_storage', ['256 GB', '512 GB'], 7000, 5000, 0]],
        ['ELE-012', 'Vertex Chromebook 14 Duo', 'electronics', 'consumer-electronic', null, 'Vertex', 'house', 'laptop', 32999, null, null, 0, [], ['ram_storage', ['256 GB', '512 GB'], 5000, 3000, 0]],

        // Smart Phones (6)
        ['PHO-001', 'Vertex X5 Pro 5G', 'smart-phones', 'others', null, 'Vertex', 'technest', 'phone', 24999, null, null, 0, ['is_featured', 'is_top'], ['ram_storage', ['128 GB', '256 GB'], 4000, 3000, 0]],
        ['PHO-002', 'Vertex X5 Lite 5G', 'smart-phones', 'others', null, 'Vertex', 'house', 'phone', 15999, null, null, 0, ['is_best'], ['ram_storage', ['128 GB', '256 GB'], 3000, 2000, 0]],
        ['PHO-003', 'Lumen Nova 12', 'smart-phones', 'others', null, 'Lumen', 'technest', 'phone', 19999, null, null, 0, ['is_top'], ['ram_storage', ['128 GB', '256 GB'], 3000, 2500, 12]],
        ['PHO-004', 'Lumen Nova 12 Max', 'smart-phones', 'others', null, 'Lumen', 'technest', 'phone', 32999, null, null, 0, ['is_featured'], ['ram_storage', ['128 GB', '256 GB'], 4000, 3000, 0]],
        ['PHO-005', 'Vertex Edge Ultra', 'smart-phones', 'others', null, 'Vertex', 'mallik', 'phone', 74999, null, null, 0, [], ['ram_storage', ['256 GB', '512 GB'], 8000, 10000, 0]],
        ['PHO-006', 'Lumen A3 Smartphone', 'smart-phones', 'others', null, 'Lumen', 'house', 'phone', 8999, 7999, 7, 45, [], null],

        // Accessories (8)
        ['ACC-001', 'Harbor Classic Steel Analog Watch', 'accessories', null, null, 'Harbor', 'urban', 'watch', 3999, 2999, 5, 26, ['is_featured', 'is_top'], null],
        ['ACC-002', 'Harbor Chronograph Black Watch', 'accessories', null, null, 'Harbor', 'house', 'watch', 5499, null, null, 15, ['is_best'], null],
        ['ACC-003', 'Vertex FitBand Smart Watch', 'accessories', null, null, 'Vertex', 'urban', 'smartwatch', 2999, 2499, null, 60, [], null],
        ['ACC-004', 'Lumen Active Fitness Band', 'accessories', null, null, 'Lumen', 'mallik', 'smartwatch', 3999, null, null, 33, [], null],
        ['ACC-005', 'Harbor Urban Laptop Backpack', 'accessories', null, null, 'Harbor', 'urban', 'bag', 1999, 1499, null, 50, ['is_best'], null],
        ['ACC-006', 'Harbor Campus Backpack', 'accessories', null, null, 'Harbor', 'house', 'bag', 1599, null, null, 42, [], null],
        ['ACC-007', 'Harbor Travel Daypack', 'accessories', null, null, 'Harbor', 'urban', 'bag', 1299, null, null, 0, [], null],
        ['ACC-008', 'Vertex Smart Watch Lite', 'accessories', null, null, 'Vertex', 'mallik', 'smartwatch', 1999, null, null, 70, [], null],

        // Furniture (5)
        ['FUR-001', 'Pinecrest Ergonomic Gaming Chair', 'furniture', 'office', 'chairs', 'Pinecrest', 'house', 'chair', 14999, 12999, 6, 11, ['is_featured', 'is_top'], null],
        ['FUR-002', 'Pinecrest Racing Gaming Chair', 'furniture', 'office', 'chairs', 'Pinecrest', 'mallik', 'chair', 12999, null, null, 8, ['is_best'], null],
        ['FUR-003', 'Pinecrest High Back Office Chair', 'furniture', 'office', 'chairs', 'Pinecrest', 'house', 'chair', 8999, 7499, null, 19, [], null],
        ['FUR-004', 'Pinecrest Mesh Executive Chair', 'furniture', 'office', 'chairs', 'Pinecrest', 'mallik', 'chair', 10999, null, null, 7, [], null],
        ['FUR-005', 'Pinecrest Pro Gaming Chair XL', 'furniture', 'office', 'chairs', 'Pinecrest', 'house', 'chair', 18999, null, null, 5, [], null],

        // Health & Beauty (3)
        ['HEA-001', 'Harbor Infrared Forehead Thermometer', 'health-beauty', null, null, 'Harbor', 'house', 'thermometer', 1499, 1199, null, 90, ['is_top'], null],
        ['HEA-002', 'Harbor Digital Non-Contact Thermometer', 'health-beauty', null, null, 'Harbor', 'mallik', 'thermometer', 1299, null, null, 64, [], null],
        ['HEA-003', 'Harbor Baby Care Thermometer', 'health-beauty', null, null, 'Harbor', 'house', 'thermometer', 999, null, null, 75, [], null],

        // Home & Garden (2)
        ['HOM-001', 'Pinecrest 260 L Frost-Free Refrigerator', 'home-garden', null, null, 'Pinecrest', 'house', 'fridge', 27999, 24999, 4, 6, ['is_featured'], null],
        ['HOM-002', 'Pinecrest 190 L Single Door Refrigerator', 'home-garden', null, null, 'Pinecrest', 'house', 'fridge', 16999, null, null, 10, [], null],

        // Toy & Games (2)
        ['TOY-001', 'Harbor Kids Bicycle 16"', 'toy-games', null, null, 'Harbor', 'house', 'bicycle', 5999, 4999, null, 16, [], null],
        ['TOY-002', 'Harbor Kids Balance Cycle 12"', 'toy-games', null, null, 'Harbor', 'house', 'bicycle', 3999, null, null, 21, [], null],
    ];

    /** Demo shops created when they are missing (approved, through CreateVendorByAdminAction). */
    private const VENDORS = [
        'urban' => ['name' => 'Riya Sharma', 'email' => 'urban.threads@example.com', 'shop_name' => 'Urban Threads', 'address' => '12 MG Road, Bengaluru'],
        'technest' => ['name' => 'Arjun Mehta', 'email' => 'technest@example.com', 'shop_name' => 'TechNest', 'address' => '45 Nehru Place, New Delhi'],
    ];

    /** @var array<int, string> temporary image copies, removed at the end */
    private array $temporary = [];

    public function __construct(
        private readonly ProductService $products,
        private readonly AttributeService $attributes,
        private readonly CreateVendorByAdminAction $createVendor,
    ) {}

    public function run(): void
    {
        $admin = Admin::query()->oldest('id')->first()
            ?? throw new RuntimeException('No admin account: run AdminSeeder first.');

        try {
            DB::transaction(function () use ($admin) {
                $owners = $this->owners($admin);
                $values = $this->attributeValues();
                $created = $updated = 0;

                foreach (array_slice(self::PRODUCTS, 0, self::COUNT) as $row) {
                    $this->seedProduct($row, $admin, $owners, $values) ? $created++ : $updated++;
                }

                $this->command?->info("Demo products: {$created} created, {$updated} updated.");
            });
        } finally {
            array_map(fn (string $path) => @unlink($path), $this->temporary);
        }
    }

    /**
     * @param  array<string, int|null>  $owners
     * @param  array<string, array<string, int>>  $values  attribute name => value label => id
     * @return bool true when created
     */
    private function seedProduct(array $row, Admin $admin, array $owners, array $values): bool
    {
        [$code, $name, $categorySlug, $subSlug, $childSlug, $brandName, $owner, $kind, $price, $offer, $days, $stock, $flags, $variants] = $row;

        $sku = self::SKU_PREFIX.$code;
        [$short, $specs] = self::KINDS[$kind];
        [$category, $sub, $child] = $this->categories($categorySlug, $subSlug, $childSlug);

        $attributes = [
            'name' => $name,
            'sku' => $sku,
            'category_id' => $category->id,
            'sub_category_id' => $sub?->id,
            'child_category_id' => $child?->id,
            'brand_id' => Brand::where('name', $brandName)->value('id'),
            'short_description' => $short,
            'long_description' => $this->longDescription($name, $short, $specs),
            'price' => $price,
            'offer_price' => $variants ? null : $offer,
            'offer_start_date' => $offer && $days ? today()->subDay()->toDateString() : null,
            'offer_end_date' => $offer && $days ? today()->addDays($days)->toDateString() : null,
            'stock_qty' => $stock,
            'video_url' => null,
            'seo_title' => null,
            'seo_description' => null,
            'status' => ProductStatus::Active->value,
        ];

        // With variants the price and stock come from the variants (ProductVariantService::mirror()).
        if ($variants) {
            unset($attributes['price'], $attributes['stock_qty']);
        }

        $existing = Product::withTrashed()->where('sku', $sku)->first();

        if ($existing?->trashed()) {
            $existing->restore();
        }

        $images = $existing ? null : $this->images($kind, $code);

        $data = new ProductData(
            attributes: $attributes,
            specifications: collect($specs)->map(fn (string $value, string $key) => ['key' => $key, 'value' => $value])->values()->all(),
            thumbnail: $images['thumbnail'] ?? null,
            gallery: $images['gallery'] ?? [],
            flags: collect(['is_featured', 'is_top', 'is_best'])->mapWithKeys(fn (string $flag) => [$flag => in_array($flag, $flags, true)])->all(),
            assignVendor: true,
            vendorId: $owners[$owner],
            hasVariants: $variants !== null,
            variants: $variants ? $this->variantRows($sku, $price, $variants, $values) : [],
        );

        if ($existing) {
            $this->products->update($existing, $data, $admin);

            return false;
        }

        $this->products->create($data, $admin);

        return true;
    }

    /**
     * Variant rows in ProductData format: every combination of the two attributes, with prices that grow with
     * the bigger options, varied stock, a unique SKU per variant and exactly one default (the first row).
     *
     * @param  array<string, array<string, int>>  $values
     * @return array<int, array<string, mixed>>
     */
    private function variantRows(string $sku, int $base, array $spec, array $values): array
    {
        $rows = [];

        if ($spec[0] === 'size_color') {
            [, $sizes, $colors, $offerPercent] = $spec;
            $lastOut = $spec[4] ?? false;

            foreach ($sizes as $s => $size) {
                foreach ($colors as $c => $color) {
                    // The largest size costs a little more (more fabric).
                    $price = $base + ($s === count($sizes) - 1 ? 100 : 0);
                    $rows[] = $this->variantRow($sku, [$size, $color], [$values['Size'][$size], $values['Color'][$color]], $price, $offerPercent, 6 + (($s * 7 + $c * 5) % 20));
                }
            }
        } else {
            [, $storages, $ramStep, $storageStep, $offerPercent] = $spec;
            $lastOut = $spec[5] ?? false;

            foreach (['8 GB', '16 GB'] as $r => $ram) {
                foreach ($storages as $s => $storage) {
                    $price = $base + $r * $ramStep + $s * $storageStep;
                    $rows[] = $this->variantRow($sku, [$ram, $storage], [$values['RAM'][$ram], $values['Storage'][$storage]], $price, $offerPercent, 4 + (($r * 5 + $s * 3) % 12));
                }
            }
        }

        if ($lastOut) {
            $rows[array_key_last($rows)]['stock_qty'] = 0; // one sold-out combination
        }

        $rows[0]['is_default'] = true;

        return $rows;
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, int>  $valueIds
     * @return array<string, mixed>
     */
    private function variantRow(string $sku, array $labels, array $valueIds, int $price, int $offerPercent, int $stock): array
    {
        return [
            'id' => null,
            'sku' => $sku.'-'.collect($labels)->map(fn (string $label) => Str::upper(Str::slug($label, '')))->implode('-'),
            'value_ids' => $valueIds,
            'price' => (string) $price,
            // Offers end in 9, like shop prices (e.g. 3149 for 10 % off 3499).
            'offer_price' => $offerPercent > 0 ? (string) (intdiv((int) round($price * (100 - $offerPercent) / 100), 10) * 10 + 9) : null,
            'stock_qty' => $stock,
            'is_active' => true,
            'is_default' => false,
            'image' => null,
            'remove_image' => false,
        ];
    }

    /**
     * Owner key => vendor id (null for in-house). Missing demo shops are created as approved; the welcome
     * email of CreateVendorByAdminAction is not sent for demo shops.
     *
     * @return array<string, int|null>
     */
    private function owners(Admin $admin): array
    {
        $owners = ['house' => null];
        $owners['mallik'] = Vendor::where('status', VendorStatus::Approved->value)->oldest('id')->value('id');

        foreach (self::VENDORS as $key => $shop) {
            $vendor = Vendor::where('shop_name', $shop['shop_name'])->first();

            if (! $vendor) {
                $vendor = Event::fakeFor(fn () => $this->createVendor->execute($admin, $shop + ['phone' => null, 'shop_phone' => null]), [VendorCreatedByAdmin::class]);
            }

            if (! $vendor->isApproved()) {
                throw new RuntimeException("Demo shop {$shop['shop_name']} exists but is not approved.");
            }

            $owners[$key] = $vendor->id;
        }

        // Without any other approved shop, the first demo shop also takes the "mallik" products.
        $owners['mallik'] ??= $owners['urban'];

        return $owners;
    }

    /**
     * Makes sure Size, Color, RAM and Storage have the values the demo variants use (existing values are kept,
     * new ones added) and returns attribute name => value label => id.
     *
     * @return array<string, array<string, int>>
     */
    private function attributeValues(): array
    {
        $wanted = [
            'Size' => [AttributeType::Select, ['S', 'M', 'L', '8', '9', '10']],
            'Color' => [AttributeType::Color, ['Red' => '#E53935', 'Green' => '#43A047', 'Blue' => '#1E88E5', 'Black' => '#212121', 'White' => '#FFFFFF']],
            'RAM' => [AttributeType::Select, ['8 GB', '16 GB']],
            'Storage' => [AttributeType::Select, ['128 GB', '256 GB', '512 GB']],
        ];

        $map = [];

        foreach ($wanted as $name => [$type, $labels]) {
            $attribute = Attribute::with('values')->where('name', $name)->first();
            $rows = $attribute
                ? $attribute->values->map(fn (AttributeValue $v) => ['id' => $v->id, 'value' => $v->value, 'color_code' => $v->color_code, 'is_active' => $v->is_active])->all()
                : [];
            $present = collect($rows)->pluck('value')->map(fn (string $v) => Str::lower($v));

            foreach ($labels as $key => $code) {
                $label = is_string($key) ? $key : $code;

                if (! $present->contains(Str::lower($label))) {
                    $rows[] = ['id' => null, 'value' => $label, 'color_code' => $type === AttributeType::Color ? $code : null, 'is_active' => true];
                }
            }

            $attribute = $this->attributes->save([
                'name' => $name,
                'type' => $attribute?->type->value ?? $type->value,
                'sort_order' => $attribute?->sort_order ?? count($map),
                'is_active' => true,
                'values' => $rows,
            ], $attribute);

            $map[$name] = $attribute->values->mapWithKeys(fn (AttributeValue $v) => [$v->value => $v->id])->all();
        }

        return $map;
    }

    /**
     * @return array{0: Category, 1: SubCategory|null, 2: ChildCategory|null}
     */
    private function categories(string $categorySlug, ?string $subSlug, ?string $childSlug): array
    {
        $category = Category::where('slug', $categorySlug)->first()
            ?? throw new RuntimeException("Category “{$categorySlug}” not found: run CategorySeeder first.");
        $sub = $subSlug ? SubCategory::where('category_id', $category->id)->where('slug', $subSlug)->first() : null;
        $child = $childSlug && $sub ? ChildCategory::where('sub_category_id', $sub->id)->where('slug', $childSlug)->first() : null;

        return [$category, $sub, $child];
    }

    /**
     * Thumbnail plus 2–4 gallery images from the template photos of this kind, as uploads (temporary copies,
     * so the template folder is never touched). Products of the same kind start on different photos.
     *
     * @return array{thumbnail: UploadedFile, gallery: array<int, UploadedFile>}
     */
    private function images(string $kind, string $code): array
    {
        $files = self::IMAGES[$kind];
        $offset = ((int) substr($code, -3)) % count($files);
        $ordered = [...array_slice($files, $offset), ...array_slice($files, 0, $offset)];
        // Gallery: the other photos first, then the thumbnail again; at least 2, at most 4.
        $gallery = collect([...array_slice($ordered, 1), $ordered[0]])->pipe(fn (Collection $c) => $c->count() >= 2 ? $c : $c->push($ordered[0]))->take(4);

        return [
            'thumbnail' => $this->upload($ordered[0]),
            'gallery' => $gallery->map(fn (string $file) => $this->upload($file))->values()->all(),
        ];
    }

    private function upload(string $file): UploadedFile
    {
        $source = base_path('template/ecommerce-frontend-template/images/'.$file);

        if (! is_file($source)) {
            throw new RuntimeException("Template image {$file} is missing.");
        }

        $copy = tempnam(sys_get_temp_dir(), 'demo-img-');
        copy($source, $copy);
        $this->temporary[] = $copy;

        // test: true — a local file, not an HTTP upload (the image service stores a copy of it).
        return new UploadedFile($copy, $file, mime_content_type($copy) ?: 'image/jpeg', null, true);
    }

    /**
     * @param  array<string, string>  $specs
     */
    private function longDescription(string $name, string $short, array $specs): string
    {
        $items = collect($specs)->map(fn (string $value, string $key) => '<li><strong>'.e($key).':</strong> '.e($value).'</li>')->implode('');

        return '<p><strong>'.e($name).'</strong>. '.e($short).'</p>'
            .'<p>Built for everyday use and backed by the brand warranty. Every order is checked before it ships, '
            .'and easy returns apply within 7 days of delivery.</p>'
            .'<ul>'.$items.'</ul>';
    }
}
