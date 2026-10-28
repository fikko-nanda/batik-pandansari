<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya pengguna berperan "teknisi" yang boleh mengakses endpoint maintenance.
 * Pengguna yang belum login tetap ditolak oleh auth:sanctum (401).
 */
class EnsureUserIsTeknisi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->peran !== 'teknisi') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini hanya untuk teknisi.',
            ], 403);
        }

        return $next($request);
    }
}
