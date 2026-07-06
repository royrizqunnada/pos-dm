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
        $out .= self::logoLine(); // logo raster (bila GD & file logo tersedia)

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

            // Lokasi antar — meja & lantai tampil BERSAMA (menonjol, tengah).
            $tag = [];
            if ($order->table_number) {
                $tag[] = 'MEJA '.$order->table_number;
            }
            if ($order->floor) {
                $tag[] = 'LT '.$order->floor;
            }
            if (! $tag && $order->shipping_cost > 0) {
                $tag[] = 'ONLINE';
            }
            if ($tag) {
                $out .= self::rule($width);
                $out .= self::ALIGN_CENTER.self::SIZE_DOUBLE.implode(' / ', $tag).self::SIZE_NORMAL."\n".self::ALIGN_LEFT;
            }

            $out .= self::rule($width);

            foreach ($items as $item) {
                $out .= self::SIZE_DOUBLE.$item->qty.'x '.strtoupper($item->name_snapshot).self::SIZE_NORMAL."\n";
            }

            $out .= self::rule($width);
            $out .= self::ALIGN_CENTER.'Total '.$items->sum('qty')." porsi\n".self::ALIGN_LEFT;
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

    /**
     * Logo sebagai raster ESC/POS (GS v 0), rata tengah. Mengembalikan '' bila
     * GD tak ada atau file logo tidak ditemukan (struk tetap jalan tanpa logo).
     *
     * @param  int  $dots  lebar cetak printer dalam titik (58mm ≈ 384).
     */
    private static function logoLine(int $dots = 384): string
    {
        $path = public_path('images/dm-kuliner-logo.png');

        if (! function_exists('imagecreatefrompng') || ! is_file($path)) {
            return '';
        }

        // Logo statis: raster dihitung SEKALI lalu di-cache (berat: decode 6MP
        // + dither). Key ikut filemtime → otomatis segar bila logo diganti.
        return \Illuminate\Support\Facades\Cache::rememberForever(
            'thermal.logo.'.$dots.'.'.((string) @filemtime($path)),
            fn (): string => self::buildLogoRaster($path, $dots),
        );
    }

    private static function buildLogoRaster(string $path, int $dots): string
    {
        $src = @imagecreatefrompng($path);
        if (! $src) {
            return '';
        }

        // Rata-kan transparansi ke latar putih.
        $sw = imagesx($src);
        $sh = imagesy($src);
        $flat = imagecreatetruecolor($sw, $sh);
        imagefilledrectangle($flat, 0, 0, $sw, $sh, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $src, 0, 0, 0, 0, $sw, $sh);
        imagedestroy($src);

        // Pangkas batas putih agar logo padat (tidak banyak margin).
        [$x0, $y0, $x1, $y1] = self::contentBox($flat, $sw, $sh);
        $cw = max(1, $x1 - $x0 + 1);
        $ch = max(1, $y1 - $y0 + 1);

        // Skala: lebar penuh (kelipatan 8), tinggi proporsional & dibatasi.
        $tw = $dots - ($dots % 8);
        $th = max(1, (int) round($ch * ($tw / $cw)));
        if ($th > 240) {
            $tw = max(8, (int) (round(($cw * (240 / $ch)) / 8) * 8));
            $th = 240;
        }

        $dst = imagecreatetruecolor($tw, $th);
        imagecopyresampled($dst, $flat, 0, 0, $x0, $y0, $tw, $th, $cw, $ch);
        imagedestroy($flat);

        // Grayscale.
        $gray = [];
        for ($y = 0; $y < $th; $y++) {
            for ($x = 0; $x < $tw; $x++) {
                $c = imagecolorat($dst, $x, $y);
                $gray[$y][$x] = 0.299 * (($c >> 16) & 0xff) + 0.587 * (($c >> 8) & 0xff) + 0.114 * ($c & 0xff);
            }
        }
        imagedestroy($dst);

        // Dither Floyd–Steinberg -> 1 bit (1 = titik hitam).
        $bit = [];
        for ($y = 0; $y < $th; $y++) {
            for ($x = 0; $x < $tw; $x++) {
                $old = $gray[$y][$x];
                $new = $old < 128 ? 0 : 255;
                $bit[$y][$x] = $new === 0 ? 1 : 0;
                $err = $old - $new;
                if ($x + 1 < $tw) {
                    $gray[$y][$x + 1] += $err * 7 / 16;
                }
                if ($y + 1 < $th) {
                    if ($x > 0) {
                        $gray[$y + 1][$x - 1] += $err * 3 / 16;
                    }
                    $gray[$y + 1][$x] += $err * 5 / 16;
                    if ($x + 1 < $tw) {
                        $gray[$y + 1][$x + 1] += $err * 1 / 16;
                    }
                }
            }
        }

        // Kemas ke GS v 0 (raster bit image).
        $bytesPerRow = intdiv($tw, 8);
        $data = '';
        for ($y = 0; $y < $th; $y++) {
            for ($bx = 0; $bx < $bytesPerRow; $bx++) {
                $byte = 0;
                for ($b = 0; $b < 8; $b++) {
                    if (! empty($bit[$y][$bx * 8 + $b])) {
                        $byte |= (0x80 >> $b);
                    }
                }
                $data .= chr($byte);
            }
        }

        $header = "\x1d\x76\x30\x00"
            .chr($bytesPerRow & 0xff).chr(($bytesPerRow >> 8) & 0xff)
            .chr($th & 0xff).chr(($th >> 8) & 0xff);

        return self::ALIGN_CENTER.$header.$data."\n".self::ALIGN_LEFT;
    }

    /**
     * Kotak konten (bukan putih) untuk memangkas margin. Ambang luminance 245.
     *
     * @return array{0:int,1:int,2:int,3:int} [x0, y0, x1, y1]
     */
    private static function contentBox(\GdImage $img, int $w, int $h): array
    {
        $x0 = $w;
        $y0 = $h;
        $x1 = 0;
        $y1 = 0;
        // Sampling tiap 2px cukup untuk cari batas (hemat waktu).
        for ($y = 0; $y < $h; $y += 2) {
            for ($x = 0; $x < $w; $x += 2) {
                $c = imagecolorat($img, $x, $y);
                $lum = 0.299 * (($c >> 16) & 0xff) + 0.587 * (($c >> 8) & 0xff) + 0.114 * ($c & 0xff);
                if ($lum < 245) {
                    $x0 = min($x0, $x);
                    $y0 = min($y0, $y);
                    $x1 = max($x1, $x);
                    $y1 = max($y1, $y);
                }
            }
        }

        if ($x1 <= $x0 || $y1 <= $y0) {
            return [0, 0, $w - 1, $h - 1]; // semua putih -> pakai penuh
        }

        return [$x0, $y0, $x1, $y1];
    }
}
