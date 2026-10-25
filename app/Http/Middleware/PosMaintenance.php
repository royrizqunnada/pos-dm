<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nonaktifkan aplikasi sementara (POS_MAINTENANCE=true di .env).
 *
 * Saat aktif, semua request (termasuk login, kasir, admin, portal vendor)
 * dijawab dengan halaman "500 | Server Error" polos, seolah server error.
 */
class PosMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.pos_maintenance')) {
            return $next($request);
        }

        return response()->view('maintenance', [], 500);
    }
}
