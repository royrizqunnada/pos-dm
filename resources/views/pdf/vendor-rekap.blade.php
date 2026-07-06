<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 11px; color: #111827; margin: 0; }
        .head { border-bottom: 2px solid #b45309; padding-bottom: 8px; margin-bottom: 12px; }
        .brand { font-size: 17px; font-weight: bold; color: #b45309; }
        .title { font-size: 13px; font-weight: bold; margin-top: 2px; }
        .muted { color: #6b7280; font-size: 10px; }
        .kpis { width: 100%; margin: 0 0 12px; border-collapse: collapse; }
        .kpis td { width: 33%; border: 1px solid #e5e7eb; padding: 8px 10px; }
        .kpis .lbl { color: #6b7280; font-size: 9px; text-transform: uppercase; }
        .kpis .val { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .accent { color: #b45309; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 5px 7px; }
        table.data th { background: #f3f4f6; text-transform: uppercase; font-size: 9px; color: #4b5563; text-align: left; }
        td.r, th.r { text-align: right; }
        table.data tfoot td { font-weight: bold; background: #f9fafb; }
        .foot { margin-top: 14px; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    <div class="head">
        <div class="brand">{{ $vendor?->name ?? 'Vendor' }} @if ($vendor?->code)<span class="muted">({{ $vendor->code }})</span>@endif</div>
        <div class="title">Rekap Penjualan Vendor</div>
        <div class="muted">{{ $periodLabel }} · dicetak {{ now()->translatedFormat('d M Y H:i') }}</div>
    </div>

    <table class="kpis">
        <tr>
            <td><div class="lbl">Transaksi</div><div class="val">{{ number_format($totals['count'], 0, ',', '.') }}</div></td>
            <td><div class="lbl">Total Porsi Terjual</div><div class="val">{{ number_format($totals['qty'], 0, ',', '.') }}</div></td>
            <td><div class="lbl">Jatah Diterima</div><div class="val accent">{{ rupiah($totals['base_owed']) }}</div></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:6%;">#</th>
                <th>Menu</th>
                <th class="r">Qty</th>
                <th class="r">Jatah Saya</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="r">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="r accent">{{ rupiah($item->base_owed) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; color:#9ca3af; padding:18px;">Belum ada penjualan pada periode ini.</td></tr>
            @endforelse
        </tbody>
        @if (! $items->isEmpty())
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td class="r">{{ number_format($totals['qty'], 0, ',', '.') }}</td>
                    <td class="r accent">{{ rupiah($totals['base_owed']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="foot">Hanya transaksi lunas yang dihitung. Angka adalah jatah/pendapatan vendor.</div>
</body>
</html>
