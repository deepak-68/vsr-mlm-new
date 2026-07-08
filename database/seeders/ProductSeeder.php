<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('product_categories')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Wellness',
                'slug' => 'wellness',
                'image' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );


        DB::table('products')->insert([
            [
                'id' => 1,
                'category_id' => 1,
                'name' => 'Life Health Care',
                'slug' => 'life-health-care',
                'sku' => 'test-1',
                'short_description' => null,
                'description' => 'A fruit and plant-based nutraceutical supplement formulated to support overall health and well-being. Made with a blend of natural ingredients, it is designed to complement daily nutrition and promote a balanced, healthier lifestyle.',
                'uses' => "Supports overall wellness\nHelps boost energy and vitality\nAids in maintaining immunity\nSupports skin and digestive health",
                'directions_for_use' => 'Take the capsules as directed by a healthcare professional or as mentioned on the product label. It is recommended to take with water after meals for better absorption and effectiveness.',
                'cautions' => 'This product is a nutraceutical supplement and not intended to diagnose, treat, cure, or prevent any disease. Keep out of reach of children and store in a cool, dry place.',
                'primary_benefits' => "Promotes overall health and wellness\nSupports natural energy levels\nHelps maintain body balance\nProvides antioxidant support",
                'ingredients' => 'A blend of fruit extracts, plant-based ingredients, herbal extracts, antioxidants, and essential vitamins and minerals derived from natural sources.',
                'price' => 1800.00,
                'cc_points' => 20.00,
                'discount_price' => 1450.00,
                'discount_percentage' => 0,
                'size' => '120',
                'brand' => 'VSR',
                'type' => null,
                'is_organic' => false,
                'stock' => 1447,
                'in_stock' => true,
                'rating' => 0.0,
                'review_count' => 0,
                'additional_info' => null,
                'status' => 1,
                'deleted_at' => null,
                'featured' => true,
                'created_at' => '2026-04-01 12:58:39',
                'updated_at' => '2026-07-06 17:29:01',
            ],
        ]);
    }
}
