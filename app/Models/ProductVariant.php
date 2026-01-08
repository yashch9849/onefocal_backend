<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price_override',
        'stock',
    ];

    protected $casts = [
        'price_override' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Prevent negative stock values
     */
    protected function stock(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => max(0, (int) $value),
        );
    }

    /**
     * Get the effective price for this variant
     * Returns price_override if set, otherwise falls back to product price
     */
    public function getEffectivePrice(): float
    {
        if ($this->price_override !== null) {
            return (float) $this->price_override;
        }
        
        // Ensure product relationship is loaded
        if (!$this->relationLoaded('product')) {
            $this->load('product');
        }
        
        return (float) $this->product->price;
    }

    /**
     * Get the effective stock for this variant
     * Returns variant stock if set, otherwise returns 0
     */
    public function getEffectiveStock(): int
    {
        return $this->stock ?? 0;
    }

    /**
     * Relationship to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship to ProductVariantAttributes (EAV pattern)
     */
    public function attributes()
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    /**
     * Get a specific EAV attribute value by name
     * Note: Renamed from getAttributeValue to avoid conflict with Laravel's Model::getAttributeValue()
     * 
     * @param string $attributeName
     * @return string|null
     */
    public function getEavAttribute(string $attributeName): ?string
    {
        $attribute = $this->attributes()->where('attribute_name', $attributeName)->first();
        return $attribute ? $attribute->attribute_value : null;
    }

    /**
     * Get all attributes as key-value array
     * 
     * @return array
     */
    public function getAttributesArray(): array
    {
        return $this->attributes()
            ->pluck('attribute_value', 'attribute_name')
            ->toArray();
    }

    /**
     * Set an attribute value
     * 
     * @param string $attributeName
     * @param string $attributeValue
     * @return void
     */
    public function setAttributeValue(string $attributeName, string $attributeValue): void
    {
        $this->attributes()->updateOrCreate(
            ['attribute_name' => $attributeName],
            ['attribute_value' => $attributeValue]
        );
    }

    /**
     * Safely update stock (prevents negative values)
     * 
     * @param int $quantity
     * @param bool $increase
     * @return bool
     */
    public function updateStock(int $quantity, bool $increase = true): bool
    {
        if ($increase) {
            $this->stock = $this->stock + $quantity;
        } else {
            $newStock = $this->stock - $quantity;
            if ($newStock < 0) {
                return false; // Insufficient stock
            }
            $this->stock = $newStock;
        }
        
        return $this->save();
    }

    /**
     * Check if variant has sufficient stock
     */
    public function hasStock(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }
}
