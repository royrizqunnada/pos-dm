<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'cashier_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'expected_cash',
        'counted_cash',
        'cash_variance',
        'total_cash_sales',
        'total_qris_sales',
        'order_count',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'integer',
        'expected_cash' => 'integer',
        'counted_cash' => 'integer',
        'cash_variance' => 'integer',
        'total_cash_sales' => 'integer',
        'total_qris_sales' => 'integer',
        'order_count' => 'integer',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Hitung total penjualan lunas (non-void) shift ini secara live.
     *
     * @return array{cash:int, qris:int, count:int}
     */
    public function liveSalesTotals(): array
    {
        $paid = $this->orders()->where('status', 'paid');

        return [
            'cash' => (int) (clone $paid)->where('payment_method', 'cash')->sum('total_amount'),
            'qris' => (int) (clone $paid)->where('payment_method', 'qris')->sum('total_amount'),
            'count' => (int) (clone $paid)->count(),
        ];
    }

    public function expectedCash(): int
    {
        return $this->opening_cash + $this->liveSalesTotals()['cash'];
    }
}
