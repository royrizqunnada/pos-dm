<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMenu extends Command
{
    protected $signature = 'import:menu
        {file? : Path ke CSV (default: database/data/menu-dmkuliner-seed.csv)}
        {--location=DM Kuliner - Randudongkal : Nama lokasi tujuan}';

    protected $description = 'Import menu multi-vendor DM Kuliner dari CSV (snapshot harga dasar + margin)';

    public function handle(): int
    {
        $file = $this->argument('file') ?: database_path('data/menu-dmkuliner-seed.csv');

        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $location = Location::firstOrCreate(
            ['name' => $this->option('location')],
            [
                'receipt_name' => 'DM KULINER',
                'address' => 'Jl. Lingkar Utara, Komplek Arjuna, Randudongkal',
                'instagram' => '@dmkuliner.id',
            ]
        );
        $this->info("Lokasi: {$location->name} (#{$location->id})");

        $handle = fopen($file, 'r');
        $header = null;
        $vendors = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);

                // Abaikan baris kosong & komentar (#).
                if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                    continue;
                }

                $row = str_getcsv($trimmed);

                // Baris pertama yang valid = header.
                if ($header === null) {
                    $header = array_map('trim', $row);

                    continue;
                }

                $data = array_combine($header, array_pad($row, count($header), null));

                $vendorCode = trim((string) $data['vendor_kode']);
                $vendorName = trim((string) $data['vendor_nama']);
                $menuName = trim((string) $data['menu']);

                if ($vendorCode === '' || $menuName === '') {
                    $skipped++;

                    continue;
                }

                // Vendor (firstOrCreate per lokasi+kode).
                $vendorKey = $location->id.'|'.$vendorCode;
                if (! isset($vendors[$vendorKey])) {
                    $vendors[$vendorKey] = Vendor::firstOrCreate(
                        ['location_id' => $location->id, 'code' => $vendorCode],
                        ['name' => $vendorName ?: $vendorCode]
                    );
                }
                $vendor = $vendors[$vendorKey];

                [$basePrice, $margin] = $this->resolvePricing(
                    sellingPrice: (int) $this->toNumber($data['harga_jual'] ?? null),
                    basePrice: $this->toNumber($data['harga_dasar'] ?? null),
                    margin: $this->toNumber($data['margin'] ?? null),
                );

                $sellingPrice = $basePrice + $margin;

                $item = MenuItem::updateOrCreate(
                    ['vendor_id' => $vendor->id, 'name' => $menuName],
                    [
                        'category' => trim((string) ($data['kategori'] ?? '')) ?: null,
                        'base_price' => $basePrice,
                        'margin' => $margin,
                        // selling_price di-set otomatis oleh model.
                        // Menu tanpa harga (harga_jual kosong/0) dinonaktifkan
                        // agar tidak muncul di kasir sampai owner isi harganya.
                        'is_available' => $sellingPrice > 0,
                        'note' => trim((string) ($data['catatan'] ?? '')) ?: null,
                    ]
                );

                $item->wasRecentlyCreated ? $created++ : $updated++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            $this->error('Import gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        fclose($handle);

        $this->newLine();
        $this->info("Vendor: ".count($vendors).' | Menu baru: '.$created.' | Diperbarui: '.$updated.' | Dilewati: '.$skipped);
        $this->info('Import selesai.');

        return self::SUCCESS;
    }

    /**
     * Terapkan aturan fallback harga_dasar / margin.
     *
     * @return array{0:int,1:int} [base_price, margin]
     */
    private function resolvePricing(int $sellingPrice, ?int $basePrice, ?int $margin): array
    {
        if ($basePrice !== null) {
            // harga_dasar ada -> margin = margin yang diisi, atau sisa harga_jual.
            $margin = $margin ?? max(0, $sellingPrice - $basePrice);

            return [$basePrice, $margin];
        }

        if ($margin !== null) {
            // harga_dasar kosong tapi margin terisi.
            return [max(0, $sellingPrice - $margin), $margin];
        }

        // Dua-duanya kosong: base = harga_jual, margin = 0 (diedit owner nanti).
        return [$sellingPrice, 0];
    }

    private function toNumber(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[^0-9\-]/', '', trim($value));

        if ($clean === '' || $clean === '-') {
            return null;
        }

        return (int) $clean;
    }
}
