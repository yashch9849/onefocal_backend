<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * Process checkout - convert cart to order
     */
    public function checkout(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->with(['items.variant.product.vendor'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return $this->error(
                'Your cart is empty. Add items to cart before checkout.',
                'EMPTY_CART',
                400
            );
        }

        // Group cart items by vendor
        $itemsByVendor = $cart->items->groupBy(function ($item) {
            return $item->variant->product->vendor_id;
        });

        $orders = [];

        DB::beginTransaction();

        try {
            foreach ($itemsByVendor as $vendorId => $items) {
                $totalAmount = 0;

                // Validate stock and calculate total for each vendor
                foreach ($items as $cartItem) {
                    $variant = $cartItem->variant;
                    $product = $variant->product;

                    // Re-check stock availability
                    if (!$variant->hasStock($cartItem->quantity)) {
                        throw new \Exception(
                            "Insufficient stock for {$variant->sku}. Only {$variant->stock} items available."
                        );
                    }

                    // Check if product is still available
                    if ($product->status !== 'active') {
                        throw new \Exception(
                            "Product {$product->name} is no longer available."
                        );
                    }

                    // Validate MOQ (Minimum Order Quantity)
                    if ($cartItem->quantity < $product->moq) {
                        throw new \Exception(
                            "Minimum order quantity for {$product->name} is {$product->moq}. You have {$cartItem->quantity} in your cart."
                        );
                    }

                    $itemPrice = $variant->getEffectivePrice();
                    $subtotal = $itemPrice * $cartItem->quantity;
                    $totalAmount += $subtotal;
                }

                // Create order
                $order = Order::create([
                    'vendor_id' => $vendorId,
                    'user_id' => $request->user()->id,
                    'status' => 'pending',
                    'total' => $totalAmount,
                ]);

                // Create order items and deduct stock
                foreach ($items as $cartItem) {
                    $variant = $cartItem->variant;

                    // Create order item
                    OrderItem::create([
                        'order_id' => $order->id,
                        'vendor_id' => $vendorId,
                        'product_variant_id' => $variant->id,
                        'quantity' => $cartItem->quantity,
                        'price' => $variant->getEffectivePrice(),
                    ]);

                    // Deduct stock using safe update method
                    if (!$variant->updateStock($cartItem->quantity, false)) {
                        throw new \Exception(
                            "Failed to update stock for {$variant->sku}. Insufficient stock."
                        );
                    }
                }

                // Create initial status history
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'changed_by' => $request->user()->id,
                ]);

                $orders[] = $order->load(['items.variant.product', 'vendor', 'user']);
            }

            // Clear cart after successful checkout
            $cart->items()->delete();

            DB::commit();

            // Return single order if only one, otherwise return array
            $responseData = count($orders) === 1 
                ? ['order' => $orders[0]]
                : ['orders' => $orders];

            return $this->success($responseData, 'Checkout completed successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(
                $e->getMessage(),
                'CHECKOUT_FAILED',
                400
            );
        }
    }
}
