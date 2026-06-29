<?php

namespace App\Filament\Pages;

use App\Models\Location;
use App\Services\SettlementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TutupHari extends Page
{
    protected string $view = 'filament.pages.tutup-hari';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Tutup Hari / Settlement';

    protected static ?string $title = 'Tutup Hari';

    protected static ?int $navigationSort = 1;

    public function getSubheading(): ?string
    {
        return 'Rekap settlement & pembayaran vendor harian.';
    }

    public ?string $date = null;

    public ?int $locationId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'manager']) ?? false;
    }

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->locationId = auth()->user()?->location_id ?? Location::query()->value('id');
    }

    /**
     * @return array<int, string>
     */
    public function getLocationOptions(): array
    {
        return Location::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function getRowsProperty(): Collection
    {
        if (! $this->locationId || ! $this->date) {
            return collect();
        }

        return app(SettlementService::class)
            ->forDate((int) $this->locationId, Carbon::parse($this->date));
    }

    /**
     * @return array<string, int>
     */
    public function getTotalsProperty(): array
    {
        return app(SettlementService::class)->totals($this->rows);
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
        $filename = 'settlement-'.($location?->id ?? 'loc').'-'.$this->date.'.csv';

        return response()->streamDownload(function () use ($rows, $location) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Lokasi', $location?->name ?? '-']);
            fputcsv($out, ['Tanggal', $this->date]);
            fputcsv($out, []);
            fputcsv($out, [
                'Kode Vendor', 'Nama Vendor', 'Rekening', 'Jml Transaksi',
                'Dibayar ke Vendor (Harga Dasar)', 'Margin Saya', 'Total Kotor',
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['code'],
                    $r['name'],
                    $r['payout_account'] ?? '',
                    $r['order_count'],
                    $r['total_base_owed'],
                    $r['total_margin'],
                    $r['total_gross'],
                ]);
            }

            $totals = app(SettlementService::class)->totals($rows);
            fputcsv($out, []);
            fputcsv($out, [
                'TOTAL', '', '', $totals['order_count'],
                $totals['total_base_owed'], $totals['total_margin'], $totals['total_gross'],
            ]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
