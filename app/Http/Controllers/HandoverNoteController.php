<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\HandoverNote;
use Illuminate\Http\Request;

class HandoverNoteController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'shift' => 'required|in:morning,evening,night',
            'notes' => 'required|string|max:3000',
        ]);

        $data['admission_id'] = $admission->id;
        $data['doctor_id']    = auth()->id();

        HandoverNote::create($data);

        return back()->with('success', 'Handover note saved.')->withFragment('tab-handover');
    }

    public function destroy(Admission $admission, HandoverNote $note)
    {
        abort_if($note->admission_id !== $admission->id, 404);
        $note->delete();

        return back()->with('success', 'Handover note deleted.')->withFragment('tab-handover');
    }
}
