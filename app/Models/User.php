<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === 1;
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->wallet()->create(['coins' => 100]);
        });
    }

    public function likedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_user_likes')
            ->withTimestamps();
    }

    public function viewedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_user_views')
            ->withTimestamps();
    }

    public function purchasedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_user_purchased')
            ->withTimestamps();
    }
}
