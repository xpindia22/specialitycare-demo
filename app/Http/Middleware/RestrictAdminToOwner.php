<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demo-install hardening: when the env var `RESTRICT_ADMIN_TO_OWNER` is set to
 * an email address, only that user can reach admin / owner / HR routes. Every
 * other authenticated user (including demo accounts with role=admin) gets a
 * 403. When the env var is unset (home + nvme production), this middleware is
 * a no-op so the existing role-based access stays intact.
 *
 * Set in .env only on the install you want to restrict:
 *   RESTRICT_ADMIN_TO_OWNER=xpindia@gmail.com
 */
class RestrictAdminToOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $ownerEmail = (string) config('app.restrict_admin_to_owner', env('RESTRICT_ADMIN_TO_OWNER', ''));
        if ($ownerEmail === '') {
            return $next($request);
        }

        $user = $request->user();
        if (!$user || strcasecmp((string) $user->email, $ownerEmail) !== 0) {
            abort(403, 'Administration is restricted on this install. Contact the install owner.');
        }

        return $next($request);
    }
}
