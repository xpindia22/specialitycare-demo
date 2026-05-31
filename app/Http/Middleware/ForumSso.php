<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single-sign-on bridge to the phpBB forum at public/forum.
 *
 * For every authenticated web request, drops a short-lived HMAC-signed
 * cookie describing the current user. The phpBB SSO extension reads this
 * cookie, verifies the signature with the shared secret, and auto-logs the
 * matching phpBB account in (creating/linking it on first visit).
 *
 * When the user is NOT authenticated (incl. immediately after logout), the
 * cookie is cleared — which the phpBB side treats as "log this SSO user
 * out of the forum too".
 *
 * The cookie is registered in bootstrap/app.php's encryptCookies(except:)
 * list so it is delivered RAW (phpBB can't run Laravel's cookie cipher).
 */
class ForumSso
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $name   = (string) config('forum_sso.cookie', 'sc_forum_sso');
        $secret = (string) config('forum_sso.secret', '');
        if ($secret === '') {
            return $response; // bridge not configured — do nothing
        }

        $payload = null;

        if (Auth::guard('web')->check()) {
            // Staff member — matched in phpBB by their real email.
            $u = Auth::guard('web')->user();
            $payload = [
                'kind'  => 'staff',
                'uid'   => (int) $u->id,
                'email' => (string) $u->email,
                'name'  => (string) $u->name,
                'roles' => (array) ($u->roles()->pluck('name')->all() ?? []),
                'exp'   => time() + (int) config('forum_sso.ttl', 7200),
            ];
        } elseif (Auth::guard('patient')->check()) {
            // Patient — matched by a stable synthetic email; phpBB drops
            // them into the restricted Patients group.
            $p = Auth::guard('patient')->user();
            $payload = [
                'kind'  => 'patient',
                'uid'   => (int) $p->id,
                'email' => $p->forumSsoEmail(),
                'name'  => (string) $p->full_name,
                'roles' => [],
                'exp'   => time() + (int) config('forum_sso.ttl', 7200),
            ];
        }

        if ($payload !== null) {
            $value = $this->sign($payload, $secret);

            $response->headers->setCookie(Cookie::create(
                name: $name,
                value: $value,
                expire: time() + (int) config('forum_sso.ttl', 7200),
                path: '/',
                domain: null,
                secure: (bool) config('forum_sso.secure', false),
                httpOnly: true,
                raw: false,
                sameSite: Cookie::SAMESITE_LAX,
            ));
        } else {
            // Clear the bridge cookie when not logged in (covers logout).
            $response->headers->setCookie(Cookie::create(
                name: $name,
                value: '',
                expire: 1,
                path: '/',
                domain: null,
                secure: (bool) config('forum_sso.secure', false),
                httpOnly: true,
                raw: false,
                sameSite: Cookie::SAMESITE_LAX,
            ));
        }

        return $response;
    }

    /**
     * base64url(json(payload)) . "." . hex(hmac_sha256(b64, secret))
     * Kept deliberately simple so the phpBB PHP side can reproduce it.
     */
    private function sign(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $b64  = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $sig  = hash_hmac('sha256', $b64, $secret);
        return $b64 . '.' . $sig;
    }
}
