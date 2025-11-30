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
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function deposit(float $amount, ?string $description = null, ?string $referenceCode = null): WalletTransaction
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'واریز به کیف پول',
            'reference_code' => $referenceCode,
        ]);
    }

    public function withdraw(float $amount, ?string $description = null): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('موجودی کیف پول کافی نیست');
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'withdraw',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'برداشت از کیف پول',
        ]);
    }

    public function purchaseProduct(Product $product, string $trackingCode): WalletTransaction
    {
        if ($this->balance < $product->price) {
            throw new \Exception('موجودی کیف پول کافی نیست');
        }

        $balanceBefore = $this->balance;
        $this->balance -= $product->price;
        $this->save();

        return $this->transactions()->create([
            'type' => 'purchase',
            'amount' => $product->price,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => 'خرید محصول: '.$product->name,
            'product_id' => $product->id,
            'reference_code' => $trackingCode,
        ]);
    }

    public function refund(float $amount, ?string $description = null, ?int $productId = null, ?string $referenceCode = null): WalletTransaction
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'refund',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'بازگشت وجه',
            'product_id' => $productId,
            'reference_code' => $referenceCode,
        ]);
    }

    public function hasBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
