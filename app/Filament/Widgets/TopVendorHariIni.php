<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopVendorHariIni extends Widget
{
    protected string $view = 'filament.widgets.top-vendor-hari-ini';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'manager']) ?? false;
    }

    public function getRows(): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('vendors', 'vendors.id', '=', 'order_items.vendor_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [now()->startOfDay(), now()->endOfDay()])
            ->groupBy('vendors.id', 'vendors.code', 'vendors.name')
            ->selectRaw('vendors.code, vendors.name')
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw('SUM(order_items.qty) as qty')
            ->selectRaw('SUM(order_items.base_price_snapshot * order_items.qty - order_items.discount_from_base) as base_owed')
            ->orderByDesc('base_owed')
            ->limit(8)
            ->get();
    }
}
