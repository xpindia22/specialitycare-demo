<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Doctor;
use App\Models\Staff;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index(Request $request) {
        $filter = $request->input('designation');

        // Build a unified people list — staff + doctors. Each row carries
        // the bits the view needs without leaking model differences.
        $staff = Staff::with('user')->whereDoesntHave('user', fn ($q) => \App\Models\User::scopeHiddenFilter($q))->orderBy('first_name')->get()->map(fn ($s) => (object) [
            'kind'         => 'staff',
            'edit_url'     => route('staff.edit', $s),
            'code'         => $s->staff_id,
            'first_name'   => $s->first_name,
            'last_name'    => $s->last_name,
            'display_name' => trim($s->first_name . ' ' . $s->last_name),
            'designation'  => $s->designation ?: 'Staff',
            'phone'        => $s->phone,
            'joined'       => $s->joining_date,
            'user'         => $s->user,
        ]);

        $doctors = Doctor::with('user')->whereDoesntHave('user', fn ($q) => \App\Models\User::scopeHiddenFilter($q))->orderBy('first_name')->get()->map(fn ($d) => (object) [
            'kind'         => 'doctor',
            'edit_url'     => route('doctors.edit', $d),
            'code'         => $d->doctor_id,
            'first_name'   => $d->first_name,
            'last_name'    => $d->last_name,
            'display_name' => 'Dr. ' . trim($d->first_name . ' ' . $d->last_name),
            'designation'  => $d->specialization ?: 'Doctor',
            'phone'        => $d->phone,
            'joined'       => $d->created_at,
            'user'         => $d->user,
        ]);

        $people = $staff->concat($doctors);

        // Build the designation dropdown options from the actual data —
        // "Doctor" always appears (every doctor is grouped under it),
        // plus every distinct staff designation. Sorted alphabetically.
        $designations = $people
            ->map(fn ($p) => $p->kind === 'doctor' ? 'Doctor' : $p->designation)
            ->unique()->filter()->sort()->values();

        // Apply the filter — special case "Doctor" matches all doctors regardless of specialization
        if ($filter) {
            $people = $people->filter(function ($p) use ($filter) {
                if ($filter === 'Doctor') return $p->kind === 'doctor';
                return $p->kind === 'staff' && $p->designation === $filter;
            });
        }

        $people = $people->sortBy(fn ($p) => mb_strtolower($p->display_name))->values();

        return view('staff.index', compact('people', 'designations', 'filter'));
    }
    public function create() {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $roles = \App\Models\User::ROLES;
        return view('staff.create', compact('departments', 'roles'));
    }
    public function store(Request $request) {
        $allRoleNames = \App\Models\Role::pluck('name')->all();
        $grantLogin = $request->boolean('grant_login');

        $rules = [
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'phone'        => 'required|string',
            'designation'  => 'required|string',
            'joining_date' => 'required|date',
            'gender'       => 'required|in:Male,Female,Other',
        ];
        if ($grantLogin) {
            $rules += [
                'email'    => 'required|email|unique:users,email',
                'roles'    => 'required|array|min:1',
                'roles.*'  => 'string|in:' . implode(',', $allRoleNames),
                'password' => 'required|string|min:6|confirmed',
            ];
        } else {
            // Email is optional when no login. If supplied, must be a valid
            // email but doesn't need to be unique against users.
            $rules['email'] = 'nullable|email';
        }
        $request->validate($rules);

        $last = Staff::orderBy('id', 'desc')->first();
        $num  = $last ? (int) filter_var($last->staff_id, FILTER_SANITIZE_NUMBER_INT) + 1 : 1;
        $staffCode = 'STF' . str_pad($num, 4, '0', STR_PAD_LEFT);

        if ($grantLogin) {
            $primary = $request->input('roles')[0];
            $user = User::create([
                'name'              => $request->first_name . ' ' . $request->last_name,
                'email'             => $request->email,
                'password'          => Hash::make($request->password),
                'role'              => $primary,
                'is_active'         => true,
                'has_system_access' => true,
            ]);
            $roleIds = \App\Models\Role::whereIn('name', $request->input('roles'))->pluck('id')->all();
            $user->roles()->sync($roleIds);
            $contactEmail = $request->email;
            $rolesLabel   = implode(',', $request->input('roles'));
        } else {
            // No-login staff still need a User row (FK is NOT NULL), but it's
            // locked: synthetic email, random password, is_active=false,
            // has_system_access=false, no role pivot rows.
            $user = User::create([
                'name'              => $request->first_name . ' ' . $request->last_name,
                'email'             => 'nolog-' . $staffCode . '@no-login.local',
                'password'          => Hash::make(bin2hex(random_bytes(16))),
                'role'              => 'staff', // legacy column NOT NULL — use a benign placeholder
                'is_active'         => false,
                'has_system_access' => false,
            ]);
            $contactEmail = $request->email ?: null;
            $rolesLabel   = '—';
        }

        $staff = Staff::create(array_merge(
            $request->except(['_token', 'password', 'password_confirmation', 'roles', 'grant_login']),
            [
                'user_id'   => $user->id,
                'staff_id'  => $staffCode,
                'is_active' => true,
                'email'     => $contactEmail ?? ('nolog-' . $staffCode . '@no-login.local'),
            ]
        ));
        ActivityLog::log('created', "Added new staff member {$staff->first_name} {$staff->last_name} ({$staff->staff_id}) — {$staff->designation}, " . ($grantLogin ? "roles: {$rolesLabel}" : "no system access"));
        return redirect()->route('staff.index')->with('success', 'Staff member added.');
    }
    public function edit(Staff $staff) {
        $staff->load('user.roles');
        $departments = Department::where('is_active', true)->get();
        $roles = User::ROLES;
        return view('staff.edit', compact('staff', 'departments', 'roles'));
    }
    public function update(Request $request, Staff $staff) {
        $allRoleNames = \App\Models\Role::pluck('name')->all();
        $hasLogin = $staff->user && $staff->user->has_system_access;

        $rules = [
            'first_name'  => 'required|string',
            'last_name'   => 'required|string',
            'phone'       => 'required|string',
            'designation' => 'required|string',
        ];
        if ($hasLogin) {
            $rules['roles']   = 'required|array|min:1';
            $rules['roles.*'] = 'string|in:' . implode(',', $allRoleNames);
            if ($request->filled('password')) {
                $rules['password'] = 'string|min:6|confirmed';
            }
        }
        $request->validate($rules);

        $staff->update(array_merge(
            $request->except(['_token', '_method', 'password', 'password_confirmation', 'email', 'role', 'roles', 'is_active', 'auto_present']),
            ['auto_present' => $request->boolean('auto_present')]
        ));

        if ($hasLogin) {
            $primary = $request->input('roles')[0];
            $userUpdates = [
                'name'      => $request->first_name . ' ' . $request->last_name,
                'role'      => $primary,
                'is_active' => $request->boolean('is_active'),
            ];
            if ($request->filled('password')) {
                $userUpdates['password'] = Hash::make($request->password);
            }
            $staff->user->update($userUpdates);

            $roleIds = \App\Models\Role::whereIn('name', $request->input('roles'))->pluck('id')->all();
            $staff->user->roles()->sync($roleIds);

            ActivityLog::log('updated', "Updated staff member {$staff->first_name} {$staff->last_name} ({$staff->staff_id}) — roles: " . implode(',', $request->input('roles')));
        } else {
            // Keep the User row's display name in sync, nothing else.
            $staff->user?->update(['name' => $request->first_name . ' ' . $request->last_name]);
            ActivityLog::log('updated', "Updated staff member {$staff->first_name} {$staff->last_name} ({$staff->staff_id}) — no system access");
        }
        return redirect()->route('staff.index')->with('success', 'Staff updated.');
    }
}
