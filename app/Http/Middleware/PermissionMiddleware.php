<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route by one or more permission keys.
 *
 * Usage:
 *   Route::middleware('permission:patients.create')->...
 *   Route::middleware('permission:hr.payroll,hr.attendance')->...   (any-of)
 *
 * 403 when the user lacks ALL of the listed permissions. Forces login
 * if the user is unauthenticated.
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$keys): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->guest(route('login'));
        }
        if (empty($keys) || !$user->canAny($keys)) {
            abort(403, 'You do not have permission to access this page.');
        }
        return $next($request);
    }
}
