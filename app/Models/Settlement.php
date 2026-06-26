<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'vendor_id',
        'date',
        'total_base_owed',
        'total_margin',
        'total_gross',
        'order_count',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'date' => 'date',
        'total_base_owed' => 'integer',
        'total_margin' => 'integer',
        'total_gross' => 'integer',
        'order_count' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
