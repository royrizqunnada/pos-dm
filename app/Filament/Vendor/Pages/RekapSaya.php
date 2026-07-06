<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Vendor;
use App\Services\SettlementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rekap penjualan per periode untuk vendor sendiri (evaluasi).
 * Hanya menampilkan jatah vendor (base_owed) — harga jual & margin owner
 * TIDAK ditampilkan.
 */
class RekapSaya extends Page
{
    protected string $view = 'filament.vendor.pages.rekap-saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Rekap Penjualan';

    protected static ?string $title = 'Rekap Penjualan Saya';

    protected static ?int $navigationSort = 2;

    // Disembunyikan sementara — belum ditampilkan ke vendor.
    // Untuk mengaktifkan lagi: hapus baris ini + method canAccess() di bawah.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public ?string $from = null;

    public ?string $to = null;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function getSubheading(): ?string
    {
        return 'Evaluasi penjualan Anda per periode.';
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

    public function getVendorProperty(): ?Vendor
    {
        return auth()->user()?->vendor;
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

    /**
     * Rincian per menu (diurut terlaris). Hanya jatah vendor.
     */
    public function getItemsProperty(): Collection
    {
        $vendor = $this->vendor;

        if (! $vendor || ! $this->from || ! $this->to) {
            return collect();
        }

        [$from, $to] = $this->range();

        return app(SettlementService::class)->vendorMenuBreakdown($vendor->id, $from, $to);
    }

    /**
     * @return array{count:int, qty:int, base_owed:int}
     */
    public function getTotalsProperty(): array
    {
        $vendor = $this->vendor;

        if (! $vendor || ! $this->from || ! $this->to) {
            return ['count' => 0, 'qty' => 0, 'base_owed' => 0];
        }

        [$from, $to] = $this->range();

        return app(SettlementService::class)->vendorTotals($vendor->id, $from, $to);
    }

    public function getDayCountProperty(): int
    {
        return (int) Carbon::parse($this->from)->diffInDays(Carbon::parse($this->to)) + 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('primary')
                ->action(fn () => $this->exportPdf()),
        ];
    }

    public function exportPdf(): Response
    {
        $vendor = $this->vendor;
        $from = Carbon::parse($this->from)->translatedFormat('d M Y');
        $to = Carbon::parse($this->to)->translatedFormat('d M Y');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.vendor-rekap', [
            'vendor' => $vendor,
            'periodLabel' => 'Periode '.$from.' – '.$to,
            'items' => $this->items,
            'totals' => $this->totals,
        ])->setPaper('a4', 'portrait');

        // streamDownload agar dikenali Livewire sebagai unduhan biner (hindari
        // error "Malformed UTF-8" saat isi PDF di-JSON-encode).
        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'rekap-'.($vendor?->code ?? 'vendor').'-'.$this->from.'_sd_'.$this->to.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
