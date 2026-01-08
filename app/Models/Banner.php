<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image_url',
        'link_type',
        'link_id',
        'position',
        'is_active',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];
}
