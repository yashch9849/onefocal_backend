<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantAttribute extends Model
{
    protected $fillable = [
        'product_variant_id',
        'attribute_name',
        'attribute_value',
    ];

    /**
     * Relationship to ProductVariant
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
