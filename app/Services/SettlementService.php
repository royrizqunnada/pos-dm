<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    /**
     * Hitung rekap settlement per vendor untuk satu lokasi & rentang tanggal.
     * Hanya order berstatus 'paid' yang dihitung (void/refund diabaikan).
     *
     * @return Collection<int, array{
     *     vendor_id:int, code:string, name:string, payout_account:?string,
     *     order_count:int, total_base_owed:int, total_margin:int, total_gross:int
     * }>
     */
    public function perVendor(int $locationId, Carbon $from, Carbon $to): Collection
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('vendors', 'vendors.id', '=', 'order_items.vendor_id')
            ->where('orders.location_id', $locationId)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->groupBy('vendors.id', 'vendors.code', 'vendors.name', 'vendors.payout_account')
            ->select([
                'vendors.id as vendor_id',
                'vendors.code',
                'vendors.name',
                'vendors.payout_account',
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                // Kurangi alokasi diskon: jatah vendor & margin owner = nilai snapshot - potongan diskon.
                DB::raw('SUM(order_items.base_price_snapshot * order_items.qty - order_items.discount_from_base) as total_base_owed'),
                DB::raw('SUM(order_items.margin_snapshot * order_items.qty - order_items.discount_from_margin) as total_margin'),
                DB::raw('SUM(order_items.selling_price_snapshot * order_items.qty - order_items.discount_share) as total_gross'),
            ])
            ->orderBy('vendors.code')
            ->get();

        return $rows->map(fn ($r) => [
            'vendor_id' => (int) $r->vendor_id,
            'code' => $r->code,
            'name' => $r->name,
            'payout_account' => $r->payout_account,
            'order_count' => (int) $r->order_count,
            'total_base_owed' => (int) $r->total_base_owed,
            'total_margin' => (int) $r->total_margin,
            'total_gross' => (int) $r->total_gross,
        ]);
    }

    /**
     * Rekap untuk satu hari penuh (00:00 - 23:59:59).
     */
    public function forDate(int $locationId, Carbon $date): Collection
    {
        return $this->perVendor(
            $locationId,
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
        );
    }

    /**
     * Total agregat dari kumpulan baris per-vendor.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{order_count:int, total_base_owed:int, total_margin:int, total_gross:int}
     */
    public function totals(Collection $rows): array
    {
        return [
            'order_count' => (int) $rows->sum('order_count'),
            'total_base_owed' => (int) $rows->sum('total_base_owed'),
            'total_margin' => (int) $rows->sum('total_margin'),
            'total_gross' => (int) $rows->sum('total_gross'),
        ];
    }
}
