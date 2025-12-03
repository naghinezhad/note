<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'coins',
        'coins_before',
        'coins_after',
        'paid_amount',
        'description',
        'product_id',
        'coin_package_id',
        'reference_code',
    ];

    protected $casts = [
        'coins' => 'integer',
        'coins_before' => 'integer',
        'coins_after' => 'integer',
        'paid_amount' => 'integer',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function coinPackage(): BelongsTo
    {
        return $this->belongsTo(CoinPackage::class);
    }
}
