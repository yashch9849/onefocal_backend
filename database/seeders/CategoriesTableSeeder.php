<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Top-level categories
        $eyewear = Category::firstOrCreate(
            ['slug' => 'eyewear', 'parent_id' => null],
            ['name' => 'Eyewear', 'is_active' => true]
        );

        $sunglasses = Category::firstOrCreate(
            ['slug' => 'sunglasses', 'parent_id' => null],
            ['name' => 'Sunglasses', 'is_active' => true]
        );

        $lenses = Category::firstOrCreate(
            ['slug' => 'lenses', 'parent_id' => null],
            ['name' => 'Lenses', 'is_active' => true]
        );

        // Eyewear → Men
        $eyewearMen = Category::firstOrCreate(
            ['slug' => 'men', 'parent_id' => $eyewear->id],
            ['name' => 'Men', 'is_active' => true]
        );

        // Sunglasses → Men (same name, different parent - allowed!)
        $sunglassesMen = Category::firstOrCreate(
            ['slug' => 'men', 'parent_id' => $sunglasses->id],
            ['name' => 'Men', 'is_active' => true]
        );

        // Lenses → Photochromic
        $photochromic = Category::firstOrCreate(
            ['slug' => 'photochromic', 'parent_id' => $lenses->id],
            ['name' => 'Photochromic', 'is_active' => true]
        );

        // Lenses → Photochromic → Hard Coated
        $hardCoated = Category::firstOrCreate(
            ['slug' => 'hard-coated', 'parent_id' => $photochromic->id],
            ['name' => 'Hard Coated', 'is_active' => true]
        );

        // Lenses → Photochromic → Hard Coated → Progressive
        $progressive = Category::firstOrCreate(
            ['slug' => 'progressive', 'parent_id' => $hardCoated->id],
            ['name' => 'Progressive', 'is_active' => true]
        );

        $this->command->info('Categories seeded successfully!');
        $this->command->info('Created structure:');
        $this->command->info('  - Eyewear → Men');
        $this->command->info('  - Sunglasses → Men');
        $this->command->info('  - Lenses → Photochromic → Hard Coated → Progressive');
    }
}
