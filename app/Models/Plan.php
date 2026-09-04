<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle',
        'is_active',
        'features',
        'is_popular',
    ];

    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
    ];
}
