<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Vendor;

class OrderItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();
        $variants = ProductVariant::with('product.vendor')->get();

        if ($orders->isEmpty() || $variants->isEmpty()) {
            return; // Skip if no orders or variants exist
        }

        foreach ($orders as $order) {
            // Get variants that belong to the order's vendor
            $vendorVariants = $variants->filter(function ($variant) use ($order) {
                return $variant->product && $variant->product->vendor_id === $order->vendor_id;
            });

            if ($vendorVariants->isEmpty()) {
                continue; // Skip if no variants for this vendor
            }

            // Add 1-4 items per order
            $itemCount = rand(1, 4);
            $selectedVariants = $vendorVariants->random(min($itemCount, $vendorVariants->count()));

            foreach ($selectedVariants as $variant) {
                // Use getEffectivePrice() to get the actual price (price_override or product price)
                $price = $variant->getEffectivePrice();
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'vendor_id' => $order->vendor_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => rand(1, 3),
                    'price' => $price,
                ]);
            }
        }
    }
}
