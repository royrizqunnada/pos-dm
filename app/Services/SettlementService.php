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
     * Agregat total (lintas vendor) untuk rentang waktu & lokasi opsional.
     * Hanya order 'paid'. Sudah memperhitungkan diskon.
     *
     * @return array{order_count:int, total_base_owed:int, total_margin:int, total_gross:int}
     */
    public function aggregate(Carbon $from, Carbon $to, ?int $locationId = null): array
    {
        $row = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->when($locationId, fn ($q) => $q->where('orders.location_id', $locationId))
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw('COALESCE(SUM(order_items.base_price_snapshot * order_items.qty - order_items.discount_from_base), 0) as total_base_owed')
            ->selectRaw('COALESCE(SUM(order_items.margin_snapshot * order_items.qty - order_items.discount_from_margin), 0) as total_margin')
            ->selectRaw('COALESCE(SUM(order_items.selling_price_snapshot * order_items.qty - order_items.discount_share), 0) as total_gross')
            ->first();

        return [
            'order_count' => (int) ($row->order_count ?? 0),
            'total_base_owed' => (int) ($row->total_base_owed ?? 0),
            'total_margin' => (int) ($row->total_margin ?? 0),
            'total_gross' => (int) ($row->total_gross ?? 0),
        ];
    }

    /**
     * Agregat HARIAN dalam SATU query untuk rentang tanggal (dikelompokkan per
     * tanggal paid_at). Dipakai dashboard agar tidak menembak 1 query per hari.
     * Hasil dikunci berdasarkan 'Y-m-d'; tanggal tanpa transaksi tidak muncul
     * (pemanggil memakai emptyAggregate() sebagai default).
     *
     * @return array<string, array{order_count:int, total_base_owed:int, total_margin:int, total_gross:int}>
     */
    public function dailySeries(Carbon $from, Carbon $to, ?int $locationId = null): array
    {
        // Ekspresi tanggal portabel lintas-DB. Di Postgres CAST AS date benar,
        // tapi di SQLite (DB test) CAST AS date malah jadi angka tahun — jadi
        // pilih fungsi tanggal sesuai driver. Aman dari injeksi (dari nama driver).
        $dateExpr = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => 'DATE(orders.paid_at)',
            'sqlite' => 'date(orders.paid_at)',
            default => 'CAST(orders.paid_at AS date)', // pgsql & lainnya
        };

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->when($locationId, fn ($q) => $q->where('orders.location_id', $locationId))
            ->selectRaw("{$dateExpr} as d")
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw('COALESCE(SUM(order_items.base_price_snapshot * order_items.qty - order_items.discount_from_base), 0) as total_base_owed')
            ->selectRaw('COALESCE(SUM(order_items.margin_snapshot * order_items.qty - order_items.discount_from_margin), 0) as total_margin')
            ->selectRaw('COALESCE(SUM(order_items.selling_price_snapshot * order_items.qty - order_items.discount_share), 0) as total_gross')
            ->groupByRaw($dateExpr)
            ->get()
            ->keyBy(fn ($r) => (string) $r->d)
            ->map(fn ($r) => [
                'order_count' => (int) $r->order_count,
                'total_base_owed' => (int) $r->total_base_owed,
                'total_margin' => (int) $r->total_margin,
                'total_gross' => (int) $r->total_gross,
            ])
            ->all();
    }

    /**
     * Nilai agregat kosong (semua nol) — default untuk hari tanpa transaksi.
     *
     * @return array{order_count:int, total_base_owed:int, total_margin:int, total_gross:int}
     */
    public function emptyAggregate(): array
    {
        return ['order_count' => 0, 'total_base_owed' => 0, 'total_margin' => 0, 'total_gross' => 0];
    }

    /**
     * Rincian per menu untuk SATU vendor (jatah vendor saja) — dipakai portal
     * vendor. Diurut terlaris. Tidak memuat harga jual/margin owner.
     *
     * @return Collection<int, object>
     */
    public function vendorMenuBreakdown(int $vendorId, Carbon $from, Carbon $to): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.vendor_id', $vendorId)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->groupBy('order_items.name_snapshot')
            ->selectRaw('order_items.name_snapshot as name')
            ->selectRaw('SUM(order_items.qty) as qty')
            ->selectRaw('SUM(order_items.base_price_snapshot * order_items.qty - order_items.discount_from_base) as base_owed')
            ->orderByDesc('qty')
            ->get();
    }

    /**
     * Total untuk SATU vendor (jumlah transaksi, porsi, jatah).
     *
     * @return array{count:int, qty:int, base_owed:int}
     */
    public function vendorTotals(int $vendorId, Carbon $from, Carbon $to): array
    {
        $row = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.vendor_id', $vendorId)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->selectRaw('COUNT(DISTINCT orders.id) as count')
            ->selectRaw('COALESCE(SUM(order_items.qty), 0) as qty')
            ->selectRaw('COALESCE(SUM(order_items.base_price_snapshot * order_items.qty - order_items.discount_from_base), 0) as base_owed')
            ->first();

        return [
            'count' => (int) ($row->count ?? 0),
            'qty' => (int) ($row->qty ?? 0),
            'base_owed' => (int) ($row->base_owed ?? 0),
        ];
    }

    /**
     * Menu terlaris dalam rentang waktu (berdasarkan qty terjual), order 'paid'.
     *
     * @return Collection<int, object>
     */
    public function topMenus(Carbon $from, Carbon $to, ?int $locationId = null, int $limit = 10): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('vendors', 'vendors.id', '=', 'order_items.vendor_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->when($locationId, fn ($q) => $q->where('orders.location_id', $locationId))
            ->groupBy('order_items.name_snapshot', 'vendors.code')
            ->selectRaw('order_items.name_snapshot as name, vendors.code as vendor_code')
            ->selectRaw('SUM(order_items.qty) as qty')
            ->selectRaw('SUM(order_items.selling_price_snapshot * order_items.qty - order_items.discount_share) as gross')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();
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
