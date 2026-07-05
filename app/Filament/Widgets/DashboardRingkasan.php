<?php

namespace App\Filament\Widgets;

use App\Services\SettlementService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class DashboardRingkasan extends Widget
{
    protected string $view = 'filament.widgets.dashboard-ringkasan';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'manager']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $s = app(SettlementService::class);

        $month = $s->aggregate(now()->startOfMonth(), now()->endOfDay());

        $topVendors = DB::table('order_items')
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
            ->limit(5)
            ->get();

        return [
            'month' => $month,
            'topVendors' => $topVendors,
            'maxBase' => (int) ($topVendors->max('base_owed') ?: 1),
        ];
    }
}
