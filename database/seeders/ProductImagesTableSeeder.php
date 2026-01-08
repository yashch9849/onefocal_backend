<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            return; // Skip if no products exist
        }

        foreach ($products as $product) {
            // Create 2-4 images per product
            $imageCount = rand(2, 4);
            
            for ($i = 0; $i < $imageCount; $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => 'https://via.placeholder.com/800x600?text=' . urlencode($product->name) . '+' . ($i + 1),
                    'position' => $i,
                ]);
            }
        }
    }
}
