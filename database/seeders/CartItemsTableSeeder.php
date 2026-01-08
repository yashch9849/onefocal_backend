<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;

class CartItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carts = Cart::all();
        $variants = ProductVariant::all();

        if ($carts->isEmpty() || $variants->isEmpty()) {
            return; // Skip if no carts or variants exist
        }

        // Add items to some carts (not all)
        foreach ($carts->random(min(2, $carts->count())) as $cart) {
            // Add 1-3 items per cart
            $itemCount = rand(1, 3);
            $selectedVariants = $variants->random(min($itemCount, $variants->count()));

            foreach ($selectedVariants as $variant) {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => rand(1, 3),
                ]);
            }
        }
    }
}
