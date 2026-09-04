<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'is_active',
    ];

    /**
     * Get the full URL for the image.
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? url('storage/' . $value) : null,
        );
    }
}
