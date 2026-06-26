<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'cashier_id',
        'shift_id',
        'order_number',
        'status',
        'payment_method',
        'total_amount',
        'discount_amount',
        'discount_borne_by',
        'paid_amount',
        'change_amount',
        'paid_at',
        'voided_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'discount_amount' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generate nomor order unik harian, mis. DM-20260626-0001.
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'DM-'.now()->format('Ymd').'-';
        $last = static::where('order_number', 'like', $prefix.'%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
