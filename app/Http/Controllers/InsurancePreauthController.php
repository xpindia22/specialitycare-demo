<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\InsurancePreauth;
use Illuminate\Http\Request;

class InsurancePreauthController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'insurer_name'  => 'required|string|max:200',
            'auth_number'   => 'nullable|string|max:100',
            'requested_at'  => 'required|date',
            'notes'         => 'nullable|string|max:2000',
        ]);

        $data['admission_id'] = $admission->id;
        $data['status']       = 'pending';

        InsurancePreauth::create($data);

        return back()->with('success', 'Pre-authorization request logged.')->withFragment('tab-preauth');
    }

    public function update(Request $request, Admission $admission, InsurancePreauth $preauth)
    {
        abort_if($preauth->admission_id !== $admission->id, 404);

        $data = $request->validate([
            'status'          => 'required|in:pending,approved,rejected,partial',
            'approved_amount' => 'nullable|numeric|min:0',
            'approved_at'     => 'nullable|date',
            'notes'           => 'nullable|string|max:2000',
        ]);

        $preauth->update($data);

        return back()->with('success', 'Pre-authorization updated.')->withFragment('tab-preauth');
    }

    public function destroy(Admission $admission, InsurancePreauth $preauth)
    {
        abort_if($preauth->admission_id !== $admission->id, 404);
        $preauth->delete();

        return back()->with('success', 'Record deleted.')->withFragment('tab-preauth');
    }
}
