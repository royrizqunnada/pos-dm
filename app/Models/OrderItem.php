<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'vendor_id',
        'name_snapshot',
        'qty',
        'base_price_snapshot',
        'margin_snapshot',
        'selling_price_snapshot',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'integer',
        'base_price_snapshot' => 'integer',
        'margin_snapshot' => 'integer',
        'selling_price_snapshot' => 'integer',
        'line_total' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
