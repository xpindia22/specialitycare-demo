<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\ActivityLog;
use App\Models\IoEntry;
use Illuminate\Http\Request;

class IoEntryController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'io_type'     => 'required|in:input,output',
            'category'    => 'required|string|max:50',
            'amount_ml'   => 'required|integer|min:1|max:9999',
            'recorded_at' => 'required|date',
            'remarks'     => 'nullable|string|max:300',
        ]);

        $allCats = IoEntry::allCategories();
        abort_unless(isset($allCats[$data['category']]), 422, 'Invalid category.');

        $data['admission_id'] = $admission->id;
        $data['recorded_by']  = auth()->id();

        IoEntry::create($data);

        ActivityLog::log('I/O Entry Added', "{$data['io_type']} {$data['amount_ml']} mL for {$admission->ipd_no}");

        return back()->with('success', 'Entry recorded.')->withFragment('tab-io');
    }

    public function destroy(Admission $admission, IoEntry $entry)
    {
        abort_if($entry->admission_id !== $admission->id, 404);
        abort_if($entry->recorded_by !== auth()->id() && !auth()->user()->isAdmin(), 403);

        $entry->delete();

        return back()->with('success', 'Entry removed.')->withFragment('tab-io');
    }

    public function print(Admission $admission)
    {
        $admission->load(['patient', 'ward', 'admittingDoctor']);
        $date = request('date', today()->toDateString());

        $entries = $admission->ioEntries()
            ->whereDate('recorded_at', $date)
            ->with('recordedBy')
            ->orderBy('recorded_at')
            ->get();

        $totalIn  = $entries->where('io_type', 'input')->sum('amount_ml');
        $totalOut = $entries->where('io_type', 'output')->sum('amount_ml');
        $balance  = $totalIn - $totalOut;

        return view('admissions.io-chart', compact('admission', 'entries', 'date', 'totalIn', 'totalOut', 'balance'));
    }
}
