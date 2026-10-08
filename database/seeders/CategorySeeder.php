<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

/**
 * Local sample data for the category menu (re-runnable).
 * Run with: php artisan db:seed --class=CategorySeeder
 */
class CategorySeeder extends Seeder
{
    /**
     * name => [icon, [sub category => [child categories]]]
     *
     * @var array<string, array{0: string, 1: array<string, array<int, string>>}>
     */
    private const TREE = [
        'Fashion' => ['fas fa-tshirt', [
            "Men's" => ['T-Shirts', 'Shirts', 'Jeans'],
            "Women's" => ['Dresses', 'Tops', 'Skirts'],
            "Kid's" => ['Boys', 'Girls'],
            'Others' => [],
        ]],
        'Electronics' => ['fas fa-tv', [
            'Consumer Electronic' => ['Televisions', 'Cameras', 'Speakers'],
            'Accessories & Parts' => ['Chargers', 'Cables'],
            'Other Brands' => [],
        ]],
        'Furniture' => ['fas fa-chair', [
            'Home' => ['Sofas', 'Beds'],
            'Office' => ['Desks', 'Chairs'],
            'Restaurant' => [],
        ]],
        'Smart Phones' => ['fas fa-mobile-alt', [
            'Apple' => [], 'Xiaomi' => [], 'Oppo' => [], 'Samsung' => [], 'Vivo' => [], 'Others' => [],
        ]],
        'Health & Beauty' => ['fas fa-heartbeat', [
            'Skin Care' => ['Moisturizers', 'Cleansers'],
            'Hair Care' => ['Shampoo', 'Conditioner'],
        ]],
        'Home & Garden' => ['fas fa-home', []],
        'Accessories' => ['fas fa-camera', []],
        'Toy & Games' => ['fas fa-gamepad', []],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::TREE as $name => [$icon, $subs]) {
            $category = Category::updateOrCreate(
                ['name' => $name],
                ['icon' => $icon, 'sort_order' => ++$order, 'is_active' => true, 'show_in_menu' => true],
            );

            $subOrder = 0;

            foreach ($subs as $subName => $children) {
                $sub = SubCategory::updateOrCreate(
                    ['category_id' => $category->id, 'name' => $subName],
                    ['sort_order' => ++$subOrder, 'is_active' => true],
                );

                $childOrder = 0;

                foreach ($children as $childName) {
                    ChildCategory::updateOrCreate(
                        ['sub_category_id' => $sub->id, 'name' => $childName],
                        ['sort_order' => ++$childOrder, 'is_active' => true],
                    );
                }
            }
        }
    }
}
