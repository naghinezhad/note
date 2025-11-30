<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\belongsToMany;

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
        'price' => 'integer',
        'likes' => 'integer',
        'views' => 'integer',
        'purchased' => 'integer',
        'is_active' => 'boolean',
        'is_3d' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function likedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'product_user_likes');
    }

    public function viewedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'product_user_views');
    }

    public function purchasedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'product_user_purchased')
            ->withTimestamps()
            ->withPivot('purchased_at');
    }
}
