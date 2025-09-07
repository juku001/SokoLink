<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserTypeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return ResponseHelper::error(
                [],
                "Unauthorized: Please log in",
                401
            );
        }

        if (!in_array($user->role, $roles)) {
            return ResponseHelper::error(
                [],
                "Forbidden: You do not have permission to access this resource.",
                403
            );
        }

        return $next($request);
    }
}
