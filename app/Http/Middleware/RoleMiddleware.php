<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return redirect()->route('login');
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $userRoles = [$user->role];

        if ($user->tipe_guru_id == 2 || ($user->tipeGuru && str_contains(strtolower($user->tipeGuru->nama_tipe), 'wakil kepala sekolah'))) {
            $userRoles[] = 'wakasek';
        }

        if (empty(array_intersect($userRoles, $roles))) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
