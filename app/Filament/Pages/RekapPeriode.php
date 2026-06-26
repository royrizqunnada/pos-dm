<?php

namespace App\Filament\Pages;

use App\Models\Location;
use App\Services\SettlementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapPeriode extends Page
{
    protected string $view = 'filament.pages.rekap-periode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Rekap Periode';

    protected static ?string $title = 'Rekap Periode — Analisa & Evaluasi';

    protected static ?int $navigationSort = 0;

    public ?string $from = null;

    public ?string $to = null;

    public ?int $locationId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'manager']) ?? false;
    }

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
        $this->locationId = auth()->user()?->location_id ?? Location::query()->value('id');
    }

    public function setPreset(string $preset): void
    {
        match ($preset) {
            'today' => [$this->from = now()->toDateString(), $this->to = now()->toDateString()],
            '7' => [$this->from = now()->subDays(6)->toDateString(), $this->to = now()->toDateString()],
            '30' => [$this->from = now()->subDays(29)->toDateString(), $this->to = now()->toDateString()],
            'month' => [$this->from = now()->startOfMonth()->toDateString(), $this->to = now()->toDateString()],
            'lastmonth' => [
                $this->from = now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $this->to = now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public function getLocationOptions(): array
    {
        return Location::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function range(): array
    {
        return [
            Carbon::parse($this->from)->startOfDay(),
            Carbon::parse($this->to)->endOfDay(),
        ];
    }

    public function getRowsProperty(): Collection
    {
        if (! $this->locationId || ! $this->from || ! $this->to) {
            return collect();
        }

        [$from, $to] = $this->range();

        return app(SettlementService::class)->perVendor((int) $this->locationId, $from, $to);
    }

    /**
     * @return array<string, int>
     */
    public function getTotalsProperty(): array
    {
        return app(SettlementService::class)->totals($this->rows);
    }

    public function getTopMenusProperty(): Collection
    {
        if (! $this->locationId || ! $this->from || ! $this->to) {
            return collect();
        }

        [$from, $to] = $this->range();

        return app(SettlementService::class)->topMenus($from, $to, (int) $this->locationId, 10);
    }

    public function getDayCountProperty(): int
    {
        return (int) Carbon::parse($this->from)->diffInDays(Carbon::parse($this->to)) + 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = $this->rows;
        $location = Location::find($this->locationId);
        $filename = 'rekap-'.$this->from.'_sd_'.$this->to.'.csv';

        return response()->streamDownload(function () use ($rows, $location) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Lokasi', $location?->name ?? '-']);
            fputcsv($out, ['Periode', $this->from.' s/d '.$this->to]);
            fputcsv($out, []);
            fputcsv($out, ['Kode', 'Vendor', 'Rekening', 'Transaksi', 'Dibayar ke Vendor', 'Margin Saya', 'Total Kotor']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['code'], $r['name'], $r['payout_account'] ?? '',
                    $r['order_count'], $r['total_base_owed'], $r['total_margin'], $r['total_gross'],
                ]);
            }

            $t = app(SettlementService::class)->totals($rows);
            fputcsv($out, []);
            fputcsv($out, ['TOTAL', '', '', $t['order_count'], $t['total_base_owed'], $t['total_margin'], $t['total_gross']]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
