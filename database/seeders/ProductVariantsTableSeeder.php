<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;

class ProductVariantsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Variants use EAV pattern for flexible attributes (color, frame_type, etc.)
     * price_override can optionally override product price
     */
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Skipping variant seeding.');
            return;
        }

        $colors = ['Black', 'Brown', 'Blue', 'Gray', 'Tortoise'];
        $frameTypes = ['Full Frame', 'Semi-Rimless', 'Rimless', 'Aviator', 'Round'];
        $frameSizes = ['Small', 'Medium', 'Large'];
        $lensTypes = ['Single Vision', 'Progressive', 'Bifocal'];

        foreach ($products as $product) {
            // Create 2-4 variants per product
            $variantCount = rand(2, 4);
            
            for ($i = 0; $i < $variantCount; $i++) {
                $color = $colors[$i % count($colors)];
                $frameType = $frameTypes[$i % count($frameTypes)];
                $frameSize = $frameSizes[$i % count($frameSizes)];
                
                // Generate SKU
                $sku = 'SKU-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 3)) 
                    . '-' . $product->id . '-' . ($i + 1);
                
                // Some variants override price, some don't (null = use product price)
                $priceOverride = ($i % 2 === 0) ? $product->price + rand(-10, 20) : null;
                
                // Create variant
                $variant = ProductVariant::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => $sku,
                    ],
                    [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'price_override' => $priceOverride, // Nullable - uses product price if null
                        'stock' => rand(10, 100),
                    ]
                );
                
                // Add attributes using EAV pattern
                $variant->setAttributeValue('color', $color);
                $variant->setAttributeValue('frame_type', $frameType);
                $variant->setAttributeValue('frame_size', $frameSize);
                
                // Add lens_type for lens products
                if (str_contains(strtolower($product->name), 'lens')) {
                    $variant->setAttributeValue('lens_type', $lensTypes[$i % count($lensTypes)]);
                }
            }
        }

        $this->command->info('Product variants seeded successfully!');
        $this->command->info("Created variants for {$products->count()} product(s)");
    }
}
