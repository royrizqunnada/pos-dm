<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'category',
        'base_price',
        'margin',
        'selling_price',
        'is_available',
        'image_path',
        'note',
    ];

    protected $casts = [
        'base_price' => 'integer',
        'margin' => 'integer',
        'selling_price' => 'integer',
        'is_available' => 'boolean',
    ];

    protected static function booted(): void
    {
        // selling_price selalu = base_price + margin (auto-hitung).
        $sync = function (MenuItem $item): void {
            $item->selling_price = (int) $item->base_price + (int) $item->margin;
        };

        static::creating($sync);
        static::updating($sync);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
