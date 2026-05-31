<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('sort_order')->get();
        $catalog = (array) config('permissions', []);
        return view('admin.roles.index', compact('roles', 'catalog'));
    }

    public function create()
    {
        $catalog = (array) config('permissions', []);
        $role = new Role(['permissions' => [], 'sort_order' => 100]);
        return view('admin.roles.form', compact('role', 'catalog'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);
        $role = Role::create([
            'name'        => $data['name'],
            'label'       => $data['label'],
            'permissions' => $data['permissions'] ?? [],
            'is_system'   => false,
            'sort_order'  => $data['sort_order'] ?? 100,
        ]);
        return redirect()->route('admin.roles.index')->with('success', "Role '$role->label' created.");
    }

    public function edit(Role $role)
    {
        $catalog = (array) config('permissions', []);
        return view('admin.roles.form', compact('role', 'catalog'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateRole($request, $role);

        // Admin role: lock all permissions on (can rename label but can't strip
        // perms — protects against accidentally locking yourself out).
        if ($role->name === 'admin') {
            $allPerms = collect((array) config('permissions', []))
                ->flatMap(fn ($g) => array_keys($g['perms'] ?? []))
                ->values()->all();
            $role->update([
                'label'       => $data['label'],
                'permissions' => $allPerms,
                'sort_order'  => $data['sort_order'] ?? $role->sort_order,
            ]);
        } else {
            $update = [
                'label'       => $data['label'],
                'permissions' => $data['permissions'] ?? [],
                'sort_order'  => $data['sort_order'] ?? $role->sort_order,
            ];
            // Custom (non-system) roles can be renamed; system roles can't.
            if (!$role->is_system && !empty($data['name']) && $data['name'] !== $role->name) {
                $update['name'] = $data['name'];
            }
            $role->update($update);
        }

        return redirect()->route('admin.roles.edit', $role)->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => "System role '$role->label' cannot be deleted."]);
        }
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => "Role '$role->label' is assigned to users — reassign them first."]);
        }
        $name = $role->label;
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', "Role '$name' deleted.");
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $catalog = (array) config('permissions', []);
        $allPermKeys = collect($catalog)->flatMap(fn ($g) => array_keys($g['perms'] ?? []))->values()->all();

        $rules = [
            'label'        => 'required|string|max:80',
            'sort_order'   => 'nullable|integer|min:1|max:9999',
            'permissions'  => 'nullable|array',
            'permissions.*'=> 'string|in:'.implode(',', $allPermKeys),
        ];
        // name only editable when creating a new role or editing a non-system role
        if (!$role || (!$role->is_system && $role->name !== 'admin')) {
            $rules['name'] = [
                'required', 'string', 'max:40',
                'regex:/^[a-z][a-z0-9_]*$/',
                $role
                    ? 'unique:roles,name,'.$role->id
                    : 'unique:roles,name',
            ];
        }
        $validated = $request->validate($rules);
        if (!isset($validated['name']) && !$role) {
            $validated['name'] = Str::slug($validated['label'], '_');
        }
        return $validated;
    }
}
