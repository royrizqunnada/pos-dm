<?php

if (! function_exists('rupiah')) {
    /**
     * Format angka menjadi Rupiah, mis. 12500 -> "Rp 12.500".
     * Dipakai lintas blade (kasir, dashboard, rekap, PDF) & kode PHP.
     */
    function rupiah(int|float|string|null $n): string
    {
        return 'Rp '.number_format((int) $n, 0, ',', '.');
    }
}
