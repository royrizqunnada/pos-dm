<?php

namespace App\Filament\Concerns;

use App\Models\Location;
use App\Services\SettlementService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export rekap settlement (PDF & CSV) untuk halaman yang menyediakan
 * properti $this->rows (Collection per-vendor) dan $this->locationId.
 * Dipakai bersama RekapPeriode & TutupHari.
 */
trait ExportsSettlement
{
    protected function settlementPdf(string $title, string $periodLabel, string $filename): StreamedResponse
    {
        $rows = $this->rows;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.settlement', [
            'title' => $title,
            'periodLabel' => $periodLabel,
            'location' => Location::find($this->locationId),
            'rows' => $rows,
            'totals' => app(SettlementService::class)->totals($rows),
        ])->setPaper('a4', 'portrait');

        // streamDownload: Livewire mengenalinya sebagai unduhan biner. Kalau pakai
        // ->download() (Response biasa), Livewire coba JSON-encode isi PDF -> error
        // "Malformed UTF-8".
        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @param  array<int, array<int, string>>  $metaRows  baris info di atas tabel (mis. Lokasi, Periode/Tanggal)
     */
    protected function settlementCsv(array $metaRows, string $filename): StreamedResponse
    {
        $rows = $this->rows;
        $totals = app(SettlementService::class)->totals($rows);

        return response()->streamDownload(function () use ($rows, $totals, $metaRows) {
            $out = fopen('php://output', 'w');

            foreach ($metaRows as $meta) {
                fputcsv($out, $meta);
            }
            fputcsv($out, []);
            fputcsv($out, ['Kode Vendor', 'Nama Vendor', 'Rekening', 'Jml Transaksi', 'Dibayar ke Vendor', 'Margin Saya', 'Total Kotor']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['code'], $r['name'], $r['payout_account'] ?? '',
                    $r['order_count'], $r['total_base_owed'], $r['total_margin'], $r['total_gross'],
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['TOTAL', '', '', $totals['order_count'], $totals['total_base_owed'], $totals['total_margin'], $totals['total_gross']]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
