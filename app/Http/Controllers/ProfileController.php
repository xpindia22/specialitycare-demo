<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Self-service account page. Any logged-in user can change their OWN
 * password here — including admin/owner accounts that have no linked
 * Staff record (those can't be reached through the Staff editor).
 */
class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();

        ActivityLog::log('updated', "Changed own account password — {$user->name} ({$user->email})");

        return back()->with('success', 'Your password has been changed.');
    }
}
