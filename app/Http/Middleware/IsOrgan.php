<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Helpers\Helper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsOrgan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->type === UserType::Organ && auth()->user()->organs()->first()) {
            return $next($request);
        }

        return Helper::errorResponse('Access denied. Admins only.', null, 403);
    }
}
