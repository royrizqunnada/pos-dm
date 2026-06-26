<?php

namespace App\Filament\Widgets;

use App\Services\SettlementService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RingkasanHariIni extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Hari Ini';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'manager']) ?? false;
    }

    protected function getStats(): array
    {
        $service = app(SettlementService::class);

        $today = $service->aggregate(now()->startOfDay(), now()->endOfDay());
        $yesterday = $service->aggregate(
            now()->subDay()->startOfDay(),
            now()->subDay()->endOfDay()
        );

        // Sparkline margin 7 hari terakhir.
        $spark = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $spark[] = $service->aggregate($d->copy()->startOfDay(), $d->copy()->endOfDay())['total_margin'];
        }

        return [
            Stat::make('Margin Saya Hari Ini', $this->rupiah($today['total_margin']))
                ->description($this->trend($today['total_margin'], $yesterday['total_margin']))
                ->descriptionIcon($today['total_margin'] >= $yesterday['total_margin'] ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($spark)
                ->color('primary'),

            Stat::make('Total Penjualan', $this->rupiah($today['total_gross']))
                ->description($today['order_count'].' transaksi hari ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Dibayar ke Vendor', $this->rupiah($today['total_base_owed']))
                ->description('Jatah vendor hari ini')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),
        ];
    }

    private function trend(int $now, int $prev): string
    {
        if ($prev === 0) {
            return $now > 0 ? 'Naik dari kemarin' : 'Belum ada transaksi';
        }

        $pct = round((($now - $prev) / $prev) * 100);

        return ($pct >= 0 ? '+' : '').$pct.'% dari kemarin';
    }

    private function rupiah(int $n): string
    {
        return 'Rp '.number_format($n, 0, ',', '.');
    }
}
