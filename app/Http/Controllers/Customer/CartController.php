<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Get or create cart for the authenticated customer
     */
    private function getOrCreateCart(Request $request): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $request->user()->id]
        );
    }

    /**
     * View cart with all items
     */
    public function viewCart(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        
        $cart->load(['items.variant.product']);

        return $this->success([
            'cart' => $cart,
            'items' => $cart->items,
            'total' => $cart->total,
        ], 'Cart retrieved successfully');
    }

    /**
     * Add item to cart
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ], [
            'variant_id.required' => 'Product variant is required.',
            'variant_id.exists' => 'Selected product variant does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($request->variant_id);

        // Check stock availability
        if (!$variant->hasStock($request->quantity)) {
            return $this->error(
                "Insufficient stock. Only {$variant->stock} items available.",
                'INSUFFICIENT_STOCK',
                400
            );
        }

        // Check if product is active
        if ($variant->product->status !== 'active') {
            return $this->error(
                'This product is not available for purchase.',
                'PRODUCT_UNAVAILABLE',
                400
            );
        }

        $cart = $this->getOrCreateCart($request);

        // Check if item already exists in cart
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $request->quantity;
            
            // Check if new total quantity exceeds stock
            if (!$variant->hasStock($newQuantity)) {
                return $this->error(
                    "Cannot add more items. Only {$variant->stock} items available (you already have {$existingItem->quantity} in cart).",
                    'INSUFFICIENT_STOCK',
                    400
                );
            }

            $existingItem->update(['quantity' => $newQuantity]);
            $existingItem->load('variant.product');

            return $this->success($existingItem, 'Cart item updated successfully');
        }

        // Create new cart item
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => $request->quantity,
        ]);

        $cartItem->load('variant.product');

        return $this->success($cartItem, 'Item added to cart successfully', 201);
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem(Request $request, $cartItemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ], [
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
        ]);

        $cart = $this->getOrCreateCart($request);

        $cartItem = CartItem::where('id', $cartItemId)
            ->where('cart_id', $cart->id)
            ->with('variant.product')
            ->first();

        if (!$cartItem) {
            return $this->notFound('Cart item not found.');
        }

        $variant = $cartItem->variant;

        // Check stock availability
        if (!$variant->hasStock($request->quantity)) {
            return $this->error(
                "Insufficient stock. Only {$variant->stock} items available.",
                'INSUFFICIENT_STOCK',
                400
            );
        }

        $cartItem->update(['quantity' => $request->quantity]);
        $cartItem->load('variant.product');

        return $this->success($cartItem, 'Cart item updated successfully');
    }

    /**
     * Remove item from cart
     */
    public function removeCartItem(Request $request, $cartItemId)
    {
        $cart = $this->getOrCreateCart($request);

        $cartItem = CartItem::where('id', $cartItemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return $this->notFound('Cart item not found.');
        }

        $cartItem->delete();

        return $this->success(null, 'Item removed from cart successfully');
    }

    /**
     * Clear entire cart
     */
    public function clearCart(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        
        $cart->items()->delete();

        return $this->success(null, 'Cart cleared successfully');
    }
}
