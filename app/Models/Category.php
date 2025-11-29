<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'description',
        'order',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public static function booted()
    {
        static::saving(function ($model) {
            if (! $model->isDirty('order')) {
                return;
            }

            $oldOrder = $model->getOriginal('order');
            $newOrder = $model->order;

            if ($oldOrder === null) {
                $oldOrder = static::max('order') + 1;
            }

            if ($newOrder == $oldOrder) {
                return;
            }

            if ($newOrder < $oldOrder) {
                static::where('id', '!=', $model->id)
                    ->whereBetween('order', [$newOrder, $oldOrder - 1])
                    ->increment('order');
            } else {
                static::where('id', '!=', $model->id)
                    ->whereBetween('order', [$oldOrder + 1, $newOrder])
                    ->decrement('order');
            }
        });

        static::saved(function () {
            static::fixOrderGaps();
        });

        static::deleted(function () {
            static::fixOrderGaps();
        });
    }

    public static function fixOrderGaps()
    {
        $items = static::orderBy('order')->get();

        $i = 1;
        foreach ($items as $item) {
            $item->order = $i++;
            $item->saveQuietly();
        }
    }
}
