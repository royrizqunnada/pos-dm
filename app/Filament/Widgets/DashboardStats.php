<?php

namespace App\Filament\Widgets;

use App\Services\SettlementService;
use Filament\Widgets\Widget;

class DashboardStats extends Widget
{
    protected string $view = 'filament.widgets.dashboard-stats';

    protected static ?int $sort = -2;

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

        // Satu query mencakup hari ini, kemarin, & 7 hari terakhir.
        $daily = $s->dailySeries(now()->subDays(6)->startOfDay(), now()->endOfDay());
        $empty = $s->emptyAggregate();
        $on = fn (int $back): array => $daily[now()->subDays($back)->format('Y-m-d')] ?? $empty;

        $today = $on(0);
        $yesterday = $on(1);

        // Seri 7 hari terakhir (lama -> hari ini) untuk sparkline.
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $days[] = $on($i);
        }
        $series = fn (string $key): array => array_map(fn ($a) => (int) $a[$key], $days);

        $avgToday = $today['order_count'] > 0 ? intdiv($today['total_gross'], $today['order_count']) : 0;

        $stats = [
            [
                'label' => 'Penjualan Hari Ini',
                'value' => $today['total_gross'],
                'money' => true,
                'trend' => $this->trend($today['total_gross'], $yesterday['total_gross']),
                'accent' => '#d97706',
                'series' => $series('total_gross'),
            ],
            [
                'label' => 'Margin Saya',
                'value' => $today['total_margin'],
                'money' => true,
                'trend' => $this->trend($today['total_margin'], $yesterday['total_margin']),
                'accent' => '#4A2410',
                'series' => $series('total_margin'),
            ],
            [
                'label' => 'Dibayar ke Vendor',
                'value' => $today['total_base_owed'],
                'money' => true,
                'trend' => $this->trend($today['total_base_owed'], $yesterday['total_base_owed']),
                'accent' => '#64748b',
                'series' => $series('total_base_owed'),
            ],
            [
                'label' => 'Transaksi Hari Ini',
                'value' => $today['order_count'],
                'money' => false,
                'suffix' => 'order',
                'sub' => 'Rata-rata Rp '.number_format($avgToday, 0, ',', '.').' / transaksi',
                'trend' => $this->trend($today['order_count'], $yesterday['order_count']),
                'accent' => '#d97706',
                'series' => $series('order_count'),
            ],
        ];

        foreach ($stats as &$st) {
            $st['spark'] = $this->sparkline($st['series']);
        }

        return ['stats' => $stats];
    }

    private function trend(int $now, int $prev): ?int
    {
        if ($prev === 0) {
            return null;
        }

        return (int) round((($now - $prev) / $prev) * 100);
    }

    /**
     * Titik polyline SVG untuk sparkline dalam kanvas 100x30.
     *
     * @param  array<int, int>  $vals
     */
    private function sparkline(array $vals): string
    {
        $n = count($vals);
        if ($n < 2) {
            return '';
        }

        $min = min($vals);
        $max = max($vals);
        $range = ($max - $min) ?: 1;
        $w = 100;
        $h = 30;
        $pad = 3;

        $points = [];
        foreach (array_values($vals) as $i => $v) {
            $x = $pad + ($i / ($n - 1)) * ($w - 2 * $pad);
            $y = $h - $pad - (($v - $min) / $range) * ($h - 2 * $pad);
            $points[] = round($x, 1).','.round($y, 1);
        }

        return implode(' ', $points);
    }
}
