<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->whereNotNull('images')->where('images', '!=', '[]')->orderBy('id')->chunk(100, function ($products) {
            foreach ($products as $product) {
                $images = json_decode($product->images, true);
                if (is_array($images)) {
                    foreach ($images as $position => $imagePath) {
                        DB::table('product_images')->insert([
                            'product_id' => $product->id,
                            'image_path' => $imagePath,
                            'position' => $position,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('images')->nullable()->after('featured');
        });

        DB::table('product_images')->orderBy('product_id')->orderBy('position')->chunk(100, function ($productImages) {
            foreach ($productImages as $productImage) {
                $product = DB::table('products')->find($productImage->product_id);
                if ($product) {
                    $images = json_decode($product->images ?? '[]', true);
                    $images[] = $productImage->image_path;
                    DB::table('products')->where('id', $product->id)->update(['images' => json_encode($images)]);
                }
            }
        });
    }
};
