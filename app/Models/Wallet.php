<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coins',
    ];

    protected $casts = [
        'coins' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function hasCoins(int $coins): bool
    {
        return $this->coins >= $coins;
    }

    public function purchasePackage(CoinPackage $package, int $paidAmount, ?string $referenceCode = null): WalletTransaction
    {
        $coinsBefore = $this->coins;
        $this->coins += $package->coins;
        $this->save();

        return $this->transactions()->create([
            'type' => 'purchase_package',
            'coins' => $package->coins,
            'coins_before' => $coinsBefore,
            'coins_after' => $this->coins,
            'paid_amount' => $paidAmount,
            'description' => 'خرید پکیج '.$package->name,
            'coin_package_id' => $package->id,
            'reference_code' => $referenceCode,
        ]);
    }

    public function purchaseProduct(Product $product, ?string $referenceCode = null): WalletTransaction
    {
        $coinsBefore = $this->coins;
        $this->coins -= $product->price;
        $this->save();

        return $this->transactions()->create([
            'type' => 'purchase_product',
            'coins' => -$product->price,
            'coins_before' => $coinsBefore,
            'coins_after' => $this->coins,
            'paid_amount' => 0,
            'description' => 'خرید کتاب '.$product->name,
            'product_id' => $product->id,
            'reference_code' => $referenceCode,
        ]);
    }
}
