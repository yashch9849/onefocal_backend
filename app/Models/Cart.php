<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the total price of all items in the cart
     */
    public function getTotalAttribute()
    {
        if (!$this->relationLoaded('items')) {
            $this->load('items.variant');
        }
        
        return $this->items->sum(function ($item) {
            if ($item->relationLoaded('variant') && $item->variant) {
                // Ensure product is loaded for getEffectivePrice()
                if (!$item->variant->relationLoaded('product')) {
                    $item->variant->load('product');
                }
                return $item->quantity * $item->variant->getEffectivePrice();
            }
            return 0;
        });
    }
}
