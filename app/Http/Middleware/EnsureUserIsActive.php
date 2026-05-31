<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();
        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been blocked.'], 401);
            }
            return redirect()->route('login')->with('error', 'Your account has been blocked. Contact your administrator.');
        }

        // Opportunistic license heartbeat — fires at most once per
        // heartbeat_interval (24h default) so installs without cron also
        // phone home. Service is a no-op if MODULES_HEARTBEAT_URL is unset
        // (i.e. on the owner's own central server).
        try {
            app(\App\Services\HeartbeatService::class)->sendIfDue();
        } catch (\Throwable) {
            // Telemetry must never break a request.
        }

        return $next($request);
    }
}
