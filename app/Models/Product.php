<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'high_quality_image',
        'low_quality_image',
        'price',
        'description',
        'likes',
        'views',
        'purchased',
        'category_id',
        'is_active',
        'is_3d',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_3d' => 'boolean',
        'likes' => 'integer',
        'views' => 'integer',
        'purchased' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
