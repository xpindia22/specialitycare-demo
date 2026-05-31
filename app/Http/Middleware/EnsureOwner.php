<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the owner panel.
 *
 * Three layers, all required:
 *   1. config('modules.owner_key') must be set (else the panel doesn't exist → 404)
 *   2. The session must carry an `owner_authed` flag (set by the login form)
 *   3. The configured key must be a non-trivial length (>= 16 chars)
 *
 * Failure always returns 404, never 401/403, so a buyer probing the URL
 * gets the same response they'd get for any other unknown route.
 */
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('modules.owner_key', '');
        if (strlen($key) < 16) abort(404);

        if (!$request->session()->get('owner_authed')) abort(404);

        // Session is bound to the OWNER_KEY in effect when the user logged in
        // (a sha256 of it is stored at login time). If the key has changed
        // since, drop the session flags so the user is forced back through
        // /owner/login. Sessions issued before this binding existed have no
        // hash → also kicked out (fail-closed on upgrade).
        $loggedInHash = (string) $request->session()->get('owner_key_hash', '');
        $currentHash  = hash('sha256', $key);
        if (!hash_equals($currentHash, $loggedInHash)) {
            $request->session()->forget(['owner_authed', 'owner_key_hash']);
            abort(404);
        }

        return $next($request);
    }
}
