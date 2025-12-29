<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Helpers\Helper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->type === UserType::Admin) {
            return $next($request);
        }

        return Helper::errorResponse('Access denied. Admins only.', null, 403);
    }
}
