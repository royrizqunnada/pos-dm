<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Vendor;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.vendor_id', $vendor->id)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->groupBy('order_items.name_snapshot')
            ->select([
                'order_items.name_snapshot as name',
                DB::raw('SUM(order_items.qty) as qty'),
                DB::raw('SUM(order_items.base_price_snapshot * order_items.qty - order_items.discount_from_base) as base_owed'),
                DB::raw('SUM(order_items.selling_price_snapshot * order_items.qty - order_items.discount_share) as gross'),
            ])
            ->orderByDesc('qty')
            ->get();
    }

    /**
     * @return array{count:int, base_owed:int, gross:int}
     */
    public function getTotalsProperty(): array
    {
        $vendor = $this->vendor;

        if (! $vendor || ! $this->date) {
            return ['count' => 0, 'base_owed' => 0, 'gross' => 0];
        }

        $date = Carbon::parse($this->date);

        $row = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.vendor_id', $vendor->id)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->select([
                DB::raw('COUNT(DISTINCT orders.id) as count'),
                DB::raw('SUM(order_items.base_price_snapshot * order_items.qty - order_items.discount_from_base) as base_owed'),
                DB::raw('SUM(order_items.selling_price_snapshot * order_items.qty - order_items.discount_share) as gross'),
            ])
            ->first();

        return [
            'count' => (int) ($row->count ?? 0),
            'base_owed' => (int) ($row->base_owed ?? 0),
            'gross' => (int) ($row->gross ?? 0),
        ];
    }
}
