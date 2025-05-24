<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && in_array($user->role_id, [User::ROLE_ADMIN, User::ROLE_GOVERNMENT, User::ROLE_SYSTEM_ADMIN])) {
            return $next($request);
        }
        abort(403, 'You are not allowed to perform this action!!!');
    }
}
