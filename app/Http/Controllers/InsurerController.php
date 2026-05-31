<?php
namespace App\Http\Controllers;

use App\Models\Insurer;
use Illuminate\Http\Request;

class InsurerController extends Controller
{
    public function index()
    {
        $insurers = Insurer::orderBy('name')->paginate(30);
        return view('insurance.insurers.index', compact('insurers'));
    }

    public function create()
    {
        return view('insurance.insurers.form', ['insurer' => new Insurer]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Insurer::create($data);
        return redirect()->route('insurers.index')->with('success', 'Insurer/TPA added.');
    }

    public function edit(Insurer $insurer)
    {
        return view('insurance.insurers.form', compact('insurer'));
    }

    public function update(Request $request, Insurer $insurer)
    {
        $insurer->update($this->validated($request));
        return redirect()->route('insurers.index')->with('success', 'Insurer/TPA updated.');
    }

    public function destroy(Insurer $insurer)
    {
        $insurer->update(['is_active' => false]);
        return back()->with('success', 'Insurer deactivated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'             => 'required|string|max:150',
            'type'             => 'required|in:insurer,tpa,govt',
            'code'             => 'nullable|string|max:50|unique:insurers,code,' . ($request->route('insurer')?->id ?? 'NULL'),
            'contact_person'   => 'nullable|string|max:120',
            'contact_phone'    => 'nullable|string|max:30',
            'contact_email'    => 'nullable|email|max:150',
            'address'          => 'nullable|string',
            'gst_no'           => 'nullable|string|max:20',
            'is_active'        => 'boolean',
        ]);
    }
}
