<?php

namespace App\Services;

class DiscountAllocator
{
    public const BORNE_OWNER = 'owner';

    public const BORNE_VENDOR = 'vendor';

    public const BORNE_SPLIT = 'split';

    /**
     * Bagikan diskon ke tiap baris secara proporsional terhadap nilai jual baris,
     * lalu tentukan berapa yang memotong jatah vendor (base) vs margin owner.
     *
     * @param  array<int|string, array{selling_total:int, base_total:int, margin_total:int}>  $lines
     * @param  int  $discountAmount  total diskon (Rupiah)
     * @param  string  $borneBy  owner|vendor|split
     * @return array<int|string, array{share:int, from_base:int, from_margin:int}>
     */
    public function allocate(array $lines, int $discountAmount, string $borneBy): array
    {
        $result = [];
        foreach (array_keys($lines) as $key) {
            $result[$key] = ['share' => 0, 'from_base' => 0, 'from_margin' => 0];
        }

        $grossTotal = array_sum(array_column($lines, 'selling_total'));

        if ($discountAmount <= 0 || $grossTotal <= 0) {
            return $result;
        }

        // Diskon tidak boleh melebihi total.
        $discountAmount = min($discountAmount, $grossTotal);

        // 1) Bagi share proporsional dengan largest-remainder (tanpa selisih pembulatan).
        $shares = [];
        $remainders = [];
        $allocated = 0;
        foreach ($lines as $key => $line) {
            $exact = $discountAmount * $line['selling_total'] / $grossTotal;
            $floor = (int) floor($exact);
            $shares[$key] = $floor;
            $remainders[$key] = $exact - $floor;
            $allocated += $floor;
        }

        $leftover = $discountAmount - $allocated;
        if ($leftover > 0) {
            // Berikan sisa 1 Rupiah ke baris dengan remainder terbesar.
            arsort($remainders);
            foreach (array_keys($remainders) as $key) {
                if ($leftover <= 0) {
                    break;
                }
                $shares[$key]++;
                $leftover--;
            }
        }

        // 2) Tentukan from_base / from_margin per baris sesuai penanggung.
        foreach ($lines as $key => $line) {
            $share = $shares[$key];
            [$fromBase, $fromMargin] = $this->splitShare($share, $line, $borneBy);
            $result[$key] = [
                'share' => $share,
                'from_base' => $fromBase,
                'from_margin' => $fromMargin,
            ];
        }

        return $result;
    }

    /**
     * @param  array{selling_total:int, base_total:int, margin_total:int}  $line
     * @return array{0:int,1:int} [from_base, from_margin]
     */
    private function splitShare(int $share, array $line, string $borneBy): array
    {
        if ($share <= 0) {
            return [0, 0];
        }

        return match ($borneBy) {
            self::BORNE_VENDOR => $this->capSpill($share, $line['base_total'], $line['margin_total']),
            self::BORNE_SPLIT => $this->splitHalf($share, $line),
            default => array_reverse(
                $this->capSpill($share, $line['margin_total'], $line['base_total'])
            ), // owner: utamakan margin, sisa ke base
        };
    }

    /**
     * Potong dari "primary"; bila tidak cukup, sisanya ke "secondary".
     *
     * @return array{0:int,1:int} [primary_taken, secondary_taken]
     */
    private function capSpill(int $share, int $primaryCap, int $secondaryCap): array
    {
        $primary = min($share, $primaryCap);
        $secondary = $share - $primary;

        return [$primary, $secondary];
    }

    /**
     * Bagi dua: setengah ke margin, setengah ke base (dengan spill bila salah satu kurang).
     *
     * @param  array{selling_total:int, base_total:int, margin_total:int}  $line
     * @return array{0:int,1:int} [from_base, from_margin]
     */
    private function splitHalf(int $share, array $line): array
    {
        $fromMargin = min(intdiv($share, 2), $line['margin_total']);
        $fromBase = min($share - $fromMargin, $line['base_total']);

        // Bila masih ada sisa (salah satu pot mentok), limpahkan ke yang lain.
        $remaining = $share - $fromBase - $fromMargin;
        if ($remaining > 0) {
            $addMargin = min($remaining, $line['margin_total'] - $fromMargin);
            $fromMargin += $addMargin;
            $fromBase += $remaining - $addMargin;
        }

        return [$fromBase, $fromMargin];
    }
}
