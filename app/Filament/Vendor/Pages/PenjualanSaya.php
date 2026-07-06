<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Vendor;
use App\Services\SettlementService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PenjualanSaya extends Page
{
    protected string $view = 'filament.vendor.pages.penjualan-saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Penjualan Saya';

    protected static ?string $title = 'Penjualan Saya';

    protected static ?int $navigationSort = 1;

    /**
     * Jadikan halaman ini sebagai beranda panel vendor (/vendor).
     */
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public function getVendorProperty(): ?Vendor
    {
        return auth()->user()?->vendor;
    }

    /**
     * Vendor dikunci HANYA melihat hari berjalan (hari ini, WIB). Dikunci di
     * server — tak ada pilihan tanggal, jadi tanggal lampau tak bisa diintip.
     */
    private function today(): Carbon
    {
        return Carbon::today();
    }

    public function getTanggalLabelProperty(): string
    {
        return $this->today()->translatedFormat('l, d M Y');
    }

    /**
     * Item terjual milik vendor ini hari ini (hanya order lunas).
     */
    public function getItemsProperty(): Collection
    {
        $vendor = $this->vendor;

        if (! $vendor) {
            return collect();
        }

        $today = $this->today();

        return app(SettlementService::class)
            ->vendorMenuBreakdown($vendor->id, $today->copy()->startOfDay(), $today->copy()->endOfDay());
    }

    /**
     * @return array{count:int, qty:int, base_owed:int}
     */
    public function getTotalsProperty(): array
    {
        $vendor = $this->vendor;

        if (! $vendor) {
            return ['count' => 0, 'qty' => 0, 'base_owed' => 0];
        }

        $today = $this->today();

        return app(SettlementService::class)
            ->vendorTotals($vendor->id, $today->copy()->startOfDay(), $today->copy()->endOfDay());
    }
}
