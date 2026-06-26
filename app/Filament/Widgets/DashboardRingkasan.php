<?php

namespace App\Filament\Widgets;

use App\Services\SettlementService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class DashboardRingkasan extends Widget
{
    protected string $view = 'filament.widgets.dashboard-ringkasan';

    protected static ?int $sort = 1;

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

        $today = $s->aggregate(now()->startOfDay(), now()->endOfDay());
        $yesterday = $s->aggregate(now()->subDay()->startOfDay(), now()->subDay()->endOfDay());
        $month = $s->aggregate(now()->startOfMonth(), now()->endOfDay());

        $avgPerTx = $today['order_count'] > 0
            ? intdiv($today['total_gross'], $today['order_count'])
            : 0;

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

        $maxBase = (int) ($topVendors->max('base_owed') ?: 1);

        return [
            'today' => $today,
            'yesterday' => $yesterday,
            'month' => $month,
            'avgPerTx' => $avgPerTx,
            'marginTrend' => $this->trendPct($today['total_margin'], $yesterday['total_margin']),
            'topVendors' => $topVendors,
            'maxBase' => $maxBase,
        ];
    }

    private function trendPct(int $now, int $prev): ?int
    {
        if ($prev === 0) {
            return null;
        }

        return (int) round((($now - $prev) / $prev) * 100);
    }
}
