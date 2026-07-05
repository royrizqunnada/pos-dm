<?php

namespace App\Support;

use App\Models\Order;

/**
 * Membangun data cetak ESC/POS (untuk printer thermal via RawBT).
 *
 * Lebar default 32 karakter = kertas 58mm (Font A). Untuk 80mm pakai 48.
 * Hasilnya berupa byte mentah ESC/POS; pemanggil meng-encode base64 lalu
 * mengirim ke RawBT via skema "rawbt:base64,<data>".
 */
class ThermalReceipt
{
    // Perintah ESC/POS.
    private const INIT = "\x1b\x40";
    private const BOLD_ON = "\x1b\x45\x01";
    private const BOLD_OFF = "\x1b\x45\x00";
    private const ALIGN_LEFT = "\x1b\x61\x00";
    private const ALIGN_CENTER = "\x1b\x61\x01";
    private const SIZE_DOUBLE = "\x1d\x21\x11"; // 2x tinggi & lebar
    private const SIZE_NORMAL = "\x1d\x21\x00";
    private const CUT = "\n\n\n\x1d\x56\x00"; // umpan + potong penuh

    /**
     * Struk pelanggan.
     */
    public static function receipt(Order $order, int $width = 32): string
    {
        $loc = $order->location;
        $rp = fn ($n) => number_format((int) $n, 0, ',', '.');

        $out = self::INIT;

        // --- Kepala (tengah) ---
        $out .= self::ALIGN_CENTER;
        $out .= self::BOLD_ON.strtoupper($loc?->receipt_name ?: 'DM Kuliner').self::BOLD_OFF."\n";
        if ($loc?->address) {
            $out .= wordwrap($loc->address, $width, "\n", true)."\n";
        }
        if ($loc?->phone) {
            $out .= $loc->phone."\n";
        }
        $out .= self::ALIGN_LEFT;
        $out .= self::rule($width);

        // --- Info transaksi ---
        $out .= self::kv('No', (string) $order->order_number);
        $out .= self::kv('Tgl', $order->created_at->format('d/m/Y H:i'));
        $out .= self::kv('Kasir', optional($order->cashier)->name ?? '-');
        if ($order->table_number) {
            $out .= self::kv('Meja', (string) $order->table_number);
        }
        if ($order->floor) {
            $out .= self::kv('Lantai', (string) $order->floor);
        }
        if ($order->shipping_cost > 0) {
            $out .= self::kv('Tipe', 'Pesanan Online');
        }

        if ($order->status === 'void') {
            $out .= self::ALIGN_CENTER.self::BOLD_ON."*** DIBATALKAN ***".self::BOLD_OFF."\n".self::ALIGN_LEFT;
        }

        $out .= self::rule($width);

        // --- Item per vendor ---
        foreach ($order->items->groupBy(fn ($i) => optional($i->vendor)->name) as $vendorName => $items) {
            $out .= '[ '.($vendorName ?: 'Lainnya')." ]\n";
            foreach ($items as $item) {
                $out .= $item->name_snapshot."\n";
                $left = '  '.$item->qty.' x '.$rp($item->selling_price_snapshot);
                $out .= self::twoCol($left, $rp($item->line_total), $width);
            }
        }

        $out .= self::rule($width);

        // --- Total ---
        $subtotal = $order->total_amount + $order->discount_amount;
        $out .= self::twoCol('Subtotal', $rp($subtotal), $width);
        if ($order->discount_amount > 0) {
            $out .= self::twoCol('Diskon', '-'.$rp($order->discount_amount), $width);
        }
        $out .= self::rule($width);
        $out .= self::BOLD_ON.self::twoCol('TOTAL', $rp($order->total_amount), $width).self::BOLD_OFF;
        $out .= self::twoCol($order->payment_method === 'qris' ? 'QRIS' : 'Tunai', $rp($order->paid_amount), $width);
        $out .= self::twoCol('Kembali', $rp($order->change_amount), $width);

        // --- Ongkir (catatan, hak kurir) ---
        if ($order->shipping_cost > 0) {
            $out .= self::rule($width);
            $out .= self::twoCol('Ongkir kurir', $rp($order->shipping_cost), $width);
            $out .= "*ongkir dibayar langsung ke kurir\n";
        }

        $out .= self::rule($width);

        // --- Kaki (tengah) ---
        $out .= self::ALIGN_CENTER;
        $out .= ($loc?->receipt_footer ?: 'Terima kasih & selamat menikmati!')."\n";
        if ($loc?->instagram) {
            $out .= 'IG: '.$loc->instagram."\n";
        }
        $out .= self::ALIGN_LEFT;

        return $out.self::CUT;
    }

    /**
     * Tiket dapur — satu tiket per vendor, teks besar, dipotong antar tiket.
     */
    public static function kitchenTickets(Order $order, int $width = 32): string
    {
        $out = '';

        foreach ($order->items->groupBy('vendor_id') as $items) {
            $vendor = $items->first()->vendor;

            $out .= self::INIT;
            $out .= self::ALIGN_CENTER;
            $out .= self::BOLD_ON."TIKET DAPUR".self::BOLD_OFF."\n";
            $out .= self::SIZE_DOUBLE.strtoupper(optional($vendor)->name ?? '-').self::SIZE_NORMAL."\n";
            if (optional($vendor)->code) {
                $out .= $vendor->code."\n";
            }
            $out .= self::ALIGN_LEFT;
            $out .= self::rule($width);

            $out .= self::kv('No', (string) $order->order_number);
            $out .= self::kv('Waktu', $order->created_at->format('d/m H:i'));

            if ($order->table_number) {
                $out .= self::SIZE_DOUBLE.'MEJA '.$order->table_number.self::SIZE_NORMAL."\n";
            } elseif ($order->floor) {
                $out .= self::SIZE_DOUBLE.'LT '.$order->floor.self::SIZE_NORMAL."\n";
            } elseif ($order->shipping_cost > 0) {
                $out .= self::SIZE_DOUBLE."ONLINE".self::SIZE_NORMAL."\n";
            }

            $out .= self::rule($width);

            foreach ($items as $item) {
                $out .= self::SIZE_DOUBLE.$item->qty.'x '.strtoupper($item->name_snapshot).self::SIZE_NORMAL."\n";
            }

            $out .= self::rule($width);
            $out .= 'Total '.$items->sum('qty')." porsi\n";
            $out .= self::CUT;
        }

        return $out;
    }

    private static function rule(int $width): string
    {
        return str_repeat('-', $width)."\n";
    }

    private static function kv(string $label, string $value): string
    {
        return str_pad($label, 6).': '.$value."\n";
    }

    /**
     * Satu baris: teks kiri + nilai kanan, dipisah spasi selebar kertas.
     */
    private static function twoCol(string $left, string $right, int $width): string
    {
        $gap = $width - mb_strlen($left) - mb_strlen($right);

        if ($gap < 1) {
            return $left.' '.$right."\n";
        }

        return $left.str_repeat(' ', $gap).$right."\n";
    }
}
