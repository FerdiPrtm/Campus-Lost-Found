<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::fail('Silakan login terlebih dahulu.', 401);
        }

        if ($roles && ! in_array($user->role, $roles, true)) {
            return ApiResponse::fail('Kamu tidak memiliki izin untuk melakukan aksi ini.', 403);
        }

        return $next($request);
    }
}