<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Category;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Products must be attached to leaf (lowest-level) categories only
     */
    public function run(): void
    {
        $vendors = Vendor::all();
        
        if ($vendors->isEmpty()) {
            $this->command->warn('No vendors found. Skipping product seeding.');
            return;
        }

        // Find leaf categories (categories with no children)
        $leafCategories = Category::whereDoesntHave('children')->get();
        
        if ($leafCategories->isEmpty()) {
            $this->command->warn('No leaf categories found. Please seed categories first.');
            return;
        }

        // Example product attached to a leaf category
        // Using "Progressive" category (Lenses → Photochromic → Hard Coated → Progressive)
        $progressiveCategory = Category::where('slug', 'progressive')
            ->whereHas('parent', function ($query) {
                $query->where('slug', 'hard-coated');
            })
            ->first();

        if ($progressiveCategory && $progressiveCategory->isLeaf()) {
            $vendor = $vendors->first();
            
            $product = Product::firstOrCreate(
                [
                    'name' => 'Premium Progressive Photochromic Lens',
                    'vendor_id' => $vendor->id,
                ],
                [
                    'vendor_id' => $vendor->id,
                    'category_id' => $progressiveCategory->id,
                    'name' => 'Premium Progressive Photochromic Lens',
                    'description' => 'High-quality progressive photochromic lens with hard coating. Automatically adjusts to light conditions.',
                    'price' => 299.99,
                    'moq' => 10, // Minimum order quantity
                    'status' => 'active',
                ]
            );

            $this->command->info("Created product: {$product->name}");
            $this->command->info("  Category: {$progressiveCategory->getFullPath()}");
            $this->command->info("  Price: \${$product->price}");
            $this->command->info("  MOQ: {$product->moq}");
        }

        // Create additional sample products for other leaf categories
        $sampleProducts = [
            [
                'name' => 'Classic Aviator Sunglasses - Men',
                'description' => 'Timeless aviator style sunglasses with UV protection',
                'price' => 89.99,
                'moq' => 5,
                'category_slug' => 'men',
                'parent_category_slug' => 'sunglasses',
            ],
            [
                'name' => 'Round Frame Eyeglasses - Men',
                'description' => 'Vintage-inspired round frame eyeglasses',
                'price' => 129.99,
                'moq' => 3,
                'category_slug' => 'men',
                'parent_category_slug' => 'eyewear',
            ],
        ];

        foreach ($sampleProducts as $productData) {
            $category = Category::where('slug', $productData['category_slug'])
                ->whereHas('parent', function ($query) use ($productData) {
                    $query->where('slug', $productData['parent_category_slug']);
                })
                ->first();

            if ($category && $category->isLeaf()) {
                $vendor = $vendors->random();
                
                Product::firstOrCreate(
                    [
                        'name' => $productData['name'],
                        'vendor_id' => $vendor->id,
                    ],
                    [
                        'vendor_id' => $vendor->id,
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'description' => $productData['description'],
                        'price' => $productData['price'],
                        'moq' => $productData['moq'],
                        'status' => 'active',
                    ]
                );
            }
        }

        $this->command->info('Products seeded successfully!');
    }
}
