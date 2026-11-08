<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nonaktifkan aplikasi sementara.
 *
 * Saat aktif, semua request (termasuk login, kasir, admin, portal vendor)
 * dijawab dengan halaman "500 | Server Error" polos, seolah server error.
 * Data tidak disentuh sama sekali.
 */
class PosMaintenance
{
    /**
     * Saklar utama: true = aplikasi dinonaktifkan, false = normal kembali.
     * (Bisa juga dinyalakan lewat POS_MAINTENANCE=true di .env.)
     */
    public const NONAKTIF = false;

    public function handle(Request $request, Closure $next): Response
    {
        $nonaktif = app()->runningUnitTests()
            ? config('app.pos_maintenance')
            : (self::NONAKTIF || config('app.pos_maintenance'));

        if (! $nonaktif) {
            return $next($request);
        }

        return response()->view('maintenance', [], 500);
    }
}
