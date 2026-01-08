<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedProduct extends Model
{
    protected $fillable = [
        'product_id',
        'section',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
