<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ExportsSettlement;
use App\Models\Location;
use App\Services\SettlementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TutupHari extends Page
{
    use ExportsSettlement;

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
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('primary')
                ->action(fn () => $this->exportPdf()),
            Action::make('export')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportPdf(): Response
    {
        $location = Location::find($this->locationId);

        return $this->settlementPdf(
            'Tutup Hari / Settlement Vendor',
            'Tanggal '.Carbon::parse($this->date)->translatedFormat('l, d M Y'),
            'settlement-'.($location?->id ?? 'loc').'-'.$this->date.'.pdf',
        );
    }

    public function exportCsv(): StreamedResponse
    {
        $location = Location::find($this->locationId);

        return $this->settlementCsv([
            ['Lokasi', $location?->name ?? '-'],
            ['Tanggal', $this->date],
        ], 'settlement-'.($location?->id ?? 'loc').'-'.$this->date.'.csv');
    }
}
