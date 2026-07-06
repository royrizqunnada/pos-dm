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

    public ?string $date = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

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
     * Item terjual milik vendor ini pada tanggal terpilih (hanya order lunas).
     */
    public function getItemsProperty(): Collection
    {
        $vendor = $this->vendor;

        if (! $vendor || ! $this->date) {
            return collect();
        }

        $date = Carbon::parse($this->date);

        return app(SettlementService::class)
            ->vendorMenuBreakdown($vendor->id, $date->copy()->startOfDay(), $date->copy()->endOfDay());
    }

    /**
     * @return array{count:int, qty:int, base_owed:int}
     */
    public function getTotalsProperty(): array
    {
        $vendor = $this->vendor;

        if (! $vendor || ! $this->date) {
            return ['count' => 0, 'qty' => 0, 'base_owed' => 0];
        }

        $date = Carbon::parse($this->date);

        return app(SettlementService::class)
            ->vendorTotals($vendor->id, $date->copy()->startOfDay(), $date->copy()->endOfDay());
    }
}
