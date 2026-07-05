<?php

namespace App\Filament\Widgets;

use App\Services\SettlementService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class GrafikPenjualan extends ChartWidget
{
    protected ?string $heading = 'Tren Penjualan & Margin';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public function getDescription(): ?string
    {
        return '14 hari terakhir — penjualan kotor dibanding margin bersih Anda.';
    }

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

        // Satu query untuk 14 hari, lalu dipetakan per tanggal.
        $series = $service->dailySeries(now()->subDays(13)->startOfDay(), now()->endOfDay());
        $empty = $service->emptyAggregate();

        $labels = [];
        $gross = [];
        $margin = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $agg = $series[$d->format('Y-m-d')] ?? $empty;
            $labels[] = $d->translatedFormat('d M');
            $gross[] = $agg['total_gross'];
            $margin[] = $agg['total_margin'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => $gross,
                    'backgroundColor' => 'rgba(217, 119, 6, 0.16)',
                    'borderColor' => '#d97706',
                    'borderWidth' => 1.5,
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                    'maxBarThickness' => 22,
                ],
                [
                    'label' => 'Margin Saya',
                    'data' => $margin,
                    'backgroundColor' => '#4A2410',
                    'borderColor' => '#4A2410',
                    'borderWidth' => 0,
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                    'maxBarThickness' => 22,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            maintainAspectRatio: false,
            layout: { padding: { top: 8 } },
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: 'rgba(148, 163, 184, 0.15)', drawTicks: false },
                    ticks: {
                        padding: 8,
                        color: '#94a3b8',
                        font: { size: 11 },
                        maxTicksLimit: 5,
                        callback: (value) => {
                            if (value >= 1000000) return (value / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
                            if (value >= 1000) return Math.round(value / 1000) + ' rb';
                            return value;
                        },
                    },
                },
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11 }, maxRotation: 0, autoSkipPadding: 12 },
                },
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 16,
                        color: '#64748b',
                        font: { size: 12, weight: '600' },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    padding: 12,
                    cornerRadius: 10,
                    boxPadding: 6,
                    usePointStyle: true,
                    titleFont: { size: 12, weight: '600' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: (ctx) => '  ' + ctx.dataset.label + ':  Rp ' + Number(ctx.parsed.y).toLocaleString('id-ID'),
                    },
                },
            },
        }
        JS);
    }
}
