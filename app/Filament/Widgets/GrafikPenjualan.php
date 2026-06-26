<?php

namespace App\Filament\Widgets;

use App\Services\SettlementService;
use Filament\Widgets\ChartWidget;

class GrafikPenjualan extends ChartWidget
{
    protected ?string $heading = 'Penjualan & Margin (14 Hari Terakhir)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'manager']) ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $service = app(SettlementService::class);

        $labels = [];
        $gross = [];
        $margin = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $agg = $service->aggregate($d->copy()->startOfDay(), $d->copy()->endOfDay());
            $labels[] = $d->format('d/m');
            $gross[] = $agg['total_gross'];
            $margin[] = $agg['total_margin'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => $gross,
                    'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Margin Saya',
                    'data' => $margin,
                    'backgroundColor' => 'rgba(22, 163, 74, 0.6)',
                    'borderColor' => '#16a34a',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => true],
            ],
        ];
    }
}
