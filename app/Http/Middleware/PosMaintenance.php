<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nonaktifkan aplikasi sementara (POS_MAINTENANCE=true di .env).
 *
 * Saat aktif, semua user diblokir dengan halaman maintenance (503),
 * kecuali owner & manager. Halaman login/logout tetap terbuka supaya
 * owner/manager masih bisa masuk.
 */
class PosMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.pos_maintenance')) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user?->hasAnyRole(['owner', 'manager'])) {
            return $next($request);
        }

        // Tamu boleh membuka halaman login (termasuk request Livewire-nya).
        if (! $user && $request->is('login', 'admin/login', 'livewire*/*')) {
            return $next($request);
        }

        if ($request->is('logout')) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }
}
