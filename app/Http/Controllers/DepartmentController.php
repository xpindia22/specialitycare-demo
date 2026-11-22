<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin → Departments: specialities shown in booking, reception and the queue boards. */
class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::ordered()->get();
        $doctors = Doctor::where('is_active', true)->with('departments')->orderBy('first_name')->get();
        $doctorsByDept = $departments->mapWithKeys(fn ($d) => [
            $d->id => $doctors->filter(fn ($doc) => in_array($d->id, $doc->departmentIds()))->values(),
        ]);
        return view('departments.index', compact('departments', 'doctorsByDept'));
    }

    public function store(Request $request)
    {
        $dept = Department::create($this->validated($request) + ['is_active' => true]);
        ActivityLog::log('created', "Added department {$dept->name} ({$dept->code})");
        return back()->with('success', "Department {$dept->name} added.");
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validated($request, $department));
        ActivityLog::log('updated', "Updated department {$department->name} ({$department->code})");
        return back()->with('success', "Department {$department->name} saved.");
    }

    public function toggle(Department $department)
    {
        $department->update(['is_active' => !$department->is_active]);
        ActivityLog::log('updated', ($department->is_active ? 'Activated' : 'Deactivated') . " department {$department->name}");
        return back()->with('success', "{$department->name} " . ($department->is_active ? 'activated.' : 'deactivated.'));
    }

    private function validated(Request $request, ?Department $dept = null): array
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('departments', 'code')->ignore($dept?->id)],
            'description'  => 'nullable|string|max:1000',
            'icon'         => ['nullable', 'string', 'max:40', 'regex:/^fa-[a-z0-9-]+$/'],
            'color'        => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'room'         => 'nullable|string|max:60',
            'token_prefix' => ['nullable', 'string', 'max:4', 'alpha_num', Rule::unique('departments', 'token_prefix')->ignore($dept?->id), Rule::notIn(['T', 't'])],
            'sort_order'   => 'nullable|integer|min:0|max:9999',
        ], [
            'token_prefix.not_in' => 'T is used by the clinic-wide token series. Pick another letter.',
            'token_prefix.alpha_num' => 'Token prefix: letters and digits only, e.g. C or C1.',
            'token_prefix.unique' => 'Another department already uses this token prefix.',
        ]);
        $data['code']         = strtoupper($data['code']);
        $data['token_prefix'] = $data['token_prefix'] ? strtoupper($data['token_prefix']) : null;
        $data['sort_order']   = $data['sort_order'] ?? 100;
        $data['show_online']  = $request->boolean('show_online');
        return $data;
    }
}
