<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use Illuminate\Http\Request;

/**
 * Owner-only viewer for the login activity log (IP + country + device).
 * Access is gated in routes/web.php to the clinic owner (isOwner()).
 */
class LoginActivityController extends Controller
{
    public function index(Request $request)
    {
        // Owner-only: 403 any authenticated non-owner (even other admins).
        abort_unless(optional(auth()->user())->isOwner(), 403);

        $q = LoginActivity::query()->orderByDesc('created_at');

        if ($s = trim((string) $request->get('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('email', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%")
                  ->orWhere('country', 'like', "%{$s}%");
            });
        }
        if ($ev = $request->get('event')) {
            $q->where('event', $ev);
        }

        $activities = $q->paginate(50)->withQueryString();

        return view('admin.login-activity', compact('activities'));
    }
}
