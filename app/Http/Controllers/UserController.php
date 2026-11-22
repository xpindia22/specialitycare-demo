<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /** List every login account, including ones with no linked Staff row. */
    public function index()
    {
        $users = User::with('roles')
            ->visible()
            ->where('has_system_access', true)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
        return view('users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::orderBy('sort_order')->get(['id', 'name', 'label']);
        return view('users.edit', compact('user', 'roles'));
    }

    /** Reset another user's password (admin action — no current-password check). */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:6|confirmed']);
        $user->forceFill(['password' => Hash::make($request->password)])->save();
        ActivityLog::log('updated', "Reset password for {$user->name} ({$user->email})");
        return back()->with('success', "{$user->name}'s password has been reset.");
    }

    /** Allow / stop this user deleting (hiding) visits. Only the owner may change it. */
    public function updateDeleteVisits(Request $request, User $user)
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Only the clinic owner can change this.');
        $allow = $request->boolean('can_delete_visits');
        $user->forceFill(['can_delete_visits' => $allow])->save();
        ActivityLog::log('updated', ($allow ? 'Allowed' : 'Stopped') . " {$user->name} ({$user->email}) " . ($allow ? 'to delete' : 'deleting') . ' visits');
        return back()->with('success', "{$user->name} " . ($allow ? 'can now delete visits.' : 'can no longer delete visits.'));
    }

    /** Update a user's assigned roles (multi-role pivot). */
    public function updateRoles(Request $request, User $user)
    {
        $allRoleNames = Role::pluck('name')->all();
        $request->validate([
            'roles'   => 'required|array|min:1',
            'roles.*' => 'string|in:' . implode(',', $allRoleNames),
        ]);

        // Guard: don't let the last active admin lose the admin role.
        $removingAdmin = $user->hasRole('admin') && !in_array('admin', $request->roles, true);
        if ($removingAdmin) {
            $otherAdmins = User::where('is_active', true)->where('id', '!=', $user->id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->count();
            if ($otherAdmins < 1) {
                return back()->with('error', 'Cannot remove admin from the last active administrator.');
            }
        }

        $roleIds = Role::whereIn('name', $request->roles)->pluck('id')->all();
        $user->roles()->sync($roleIds);
        // Keep the legacy single-role string in sync with the primary role.
        $user->forceFill(['role' => $request->roles[0]])->save();

        ActivityLog::log('updated', "Updated roles for {$user->name} ({$user->email}) — " . implode(',', $request->roles));
        return back()->with('success', "{$user->name}'s roles updated.");
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', "You cannot block your own account.");
        }
        if ($user->role === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count() <= 1 && $user->is_active) {
            return back()->with('error', "Cannot block the last active administrator.");
        }

        $user->is_active = ! $user->is_active;
        if (! $user->is_active) {
            $user->remember_token = Str::random(60);
        }
        $user->save();

        if (! $user->is_active) {
            $this->purgeUserSessions($user->id);
        }

        $verb = $user->is_active ? 'unblocked' : 'blocked';
        ActivityLog::log($verb, "{$verb} login for {$user->name} ({$user->email}) — role: {$user->role}");

        return back()->with('success', "{$user->name}'s login has been " . ($user->is_active ? 'enabled' : 'blocked') . '.');
    }

    private function purgeUserSessions(int $userId): void
    {
        $driver = config('session.driver');

        if ($driver === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $userId)->delete();
            return;
        }

        if ($driver === 'file') {
            $path = config('session.files');
            if (! is_dir($path)) return;
            $needle = '"login_web_' . sha1('Illuminate\Auth\SessionGuard') . '"';
            foreach (glob($path . '/*') as $file) {
                if (! is_file($file)) continue;
                $contents = @file_get_contents($file);
                if ($contents === false) continue;
                $payload = @unserialize(base64_decode($contents)) ?: @unserialize($contents);
                if (! is_array($payload)) continue;
                foreach ($payload as $key => $val) {
                    if (str_starts_with($key, 'login_web_') && (int) $val === $userId) {
                        @unlink($file);
                        break;
                    }
                }
            }
        }
    }
}
