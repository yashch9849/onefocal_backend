<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,           // 1. Create roles first
            UsersTableSeeder::class,           // 2. Create users (includes vendors via 1:1 relationship)
            CategoriesTableSeeder::class,      // 3. Create categories
            ProductsTableSeeder::class,        // 4. Create products (requires vendors)
            ProductVariantsTableSeeder::class, // 5. Create product variants (requires products)
            ProductImagesTableSeeder::class,   // 6. Create product images (requires products)
            CartsTableSeeder::class,           // 7. Create carts (requires customers)
            CartItemsTableSeeder::class,       // 8. Create cart items (requires carts & variants)
            OrdersTableSeeder::class,          // 9. Create orders (requires customers & vendors)
            OrderItemsTableSeeder::class,      // 10. Create order items (requires orders & variants)
        ]);
    }
}
