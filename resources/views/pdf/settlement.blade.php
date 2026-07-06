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
        .kpis td { width: 25%; border: 1px solid #e5e7eb; padding: 8px 10px; vertical-align: top; }
        .kpis .lbl { color: #6b7280; font-size: 9px; text-transform: uppercase; }
        .kpis .val { font-size: 14px; font-weight: bold; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 5px 7px; }
        table.data th { background: #f3f4f6; text-transform: uppercase; font-size: 9px; color: #4b5563; text-align: left; }
        td.r, th.r { text-align: right; }
        table.data tfoot td { font-weight: bold; background: #f9fafb; }
        .accent { color: #b45309; }
        .foot { margin-top: 14px; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    <div class="head">
        <div class="brand">{{ $location?->name ?? 'DM Kuliner' }}</div>
        <div class="title">{{ $title }}</div>
        <div class="muted">{{ $periodLabel }} · dicetak {{ now()->translatedFormat('d M Y H:i') }}</div>
    </div>

    <table class="kpis">
        <tr>
            <td><div class="lbl">Total Transaksi</div><div class="val">{{ number_format($totals['order_count'], 0, ',', '.') }}</div></td>
            <td><div class="lbl">Total Uang Masuk</div><div class="val">{{ rupiah($totals['total_gross']) }}</div></td>
            <td><div class="lbl">Dibayar ke Vendor</div><div class="val">{{ rupiah($totals['total_base_owed']) }}</div></td>
            <td><div class="lbl">Margin Saya</div><div class="val accent">{{ rupiah($totals['total_margin']) }}</div></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Vendor</th>
                <th class="r">Trx</th>
                <th class="r">Dibayar</th>
                <th class="r">Margin</th>
                <th class="r">Total Kotor</th>
                <th>Rekening</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="r">{{ number_format($row['order_count'], 0, ',', '.') }}</td>
                    <td class="r">{{ rupiah($row['total_base_owed']) }}</td>
                    <td class="r accent">{{ rupiah($row['total_margin']) }}</td>
                    <td class="r">{{ rupiah($row['total_gross']) }}</td>
                    <td>{{ $row['payout_account'] ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; color:#9ca3af; padding:18px;">Belum ada transaksi lunas pada periode ini.</td></tr>
            @endforelse
        </tbody>
        @if (! $rows->isEmpty())
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td class="r">{{ number_format($totals['order_count'], 0, ',', '.') }}</td>
                    <td class="r">{{ rupiah($totals['total_base_owed']) }}</td>
                    <td class="r accent">{{ rupiah($totals['total_margin']) }}</td>
                    <td class="r">{{ rupiah($totals['total_gross']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="foot">Hanya transaksi lunas yang dihitung. Pesanan dibatalkan (void) tidak termasuk.</div>
</body>
</html>
