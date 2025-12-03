<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'coins',
        'price',
        'discount_percentage',
        'is_active',
        'link_cafebazaar',
        'link_myket',
    ];

    protected $casts = [
        'coins' => 'integer',
        'price' => 'integer',
        'discount_percentage' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'final_price',
        'discount_amount',
    ];

    public function getFinalPriceAttribute(): int
    {
        if ($this->discount_percentage > 0) {
            return $this->price - $this->getDiscountAmountAttribute();
        }

        return $this->price;
    }

    public function getDiscountAmountAttribute(): int
    {
        if ($this->discount_percentage > 0) {
            return (int) ($this->price * $this->discount_percentage / 100);
        }

        return 0;
    }

    public function hasDiscount(): bool
    {
        return $this->discount_percentage > 0;
    }
}
