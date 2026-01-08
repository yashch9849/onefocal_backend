<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_variant_id', 'quantity'];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the subtotal for this cart item
     */
    public function getSubtotalAttribute()
    {
        if (!$this->relationLoaded('variant') || !$this->variant) {
            $this->load('variant.product');
        }
        
        // Handle case where variant was deleted
        if (!$this->variant) {
            return 0;
        }
        
        return $this->quantity * $this->variant->getEffectivePrice();
    }
}
