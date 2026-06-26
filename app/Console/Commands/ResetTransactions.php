<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Settlement;
use App\Models\Shift;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTransactions extends Command
{
    protected $signature = 'pos:reset-transactions {--force : Lewati konfirmasi}';

    protected $description = 'Hapus SEMUA data transaksi (order, item, shift, settlement) untuk memulai live. Master data (vendor, menu, harga, user, lokasi) tetap aman.';

    public function handle(): int
    {
        $counts = [
            'Transaksi (orders)' => Order::count(),
            'Item transaksi (order_items)' => OrderItem::count(),
            'Shift kasir (shifts)' => Shift::count(),
            'Settlement' => Settlement::count(),
        ];

        $this->info('Data transaksi yang akan DIHAPUS:');
        $this->table(['Tabel', 'Jumlah'], collect($counts)->map(fn ($v, $k) => [$k, $v])->values());
        $this->newLine();
        $this->warn('Master data (vendor, menu, harga, pengguna, lokasi) TIDAK akan dihapus.');

        if (array_sum($counts) === 0) {
            $this->info('Tidak ada data transaksi. Sudah bersih.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Yakin hapus semua data transaksi di atas? Tindakan ini tidak bisa dibatalkan.')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Urutan aman terhadap foreign key.
            OrderItem::query()->delete();
            Order::query()->delete();
            Settlement::query()->delete();
            Shift::query()->delete();
        });

        $this->newLine();
        $this->info('✓ Data transaksi berhasil dibersihkan. Sistem siap untuk live.');

        return self::SUCCESS;
    }
}
