<?php
// FILE: app/Http/Controllers/DietChartController.php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\DietChart;
use Illuminate\Http\Request;

class DietChartController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $validated = $request->validate([
            'meal_time'    => 'required|in:morning,afternoon,evening,night',
            'instructions' => 'required|string|max:1000',
            'date'         => 'required|date',
        ]);

        $admission->dietCharts()->create([
            'meal_time'    => $validated['meal_time'],
            'instructions' => $validated['instructions'],
            'date'         => $validated['date'],
            'created_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Diet entry added.');
    }

    public function destroy(Admission $admission, DietChart $diet)
    {
        abort_if($diet->admission_id !== $admission->id, 403);
        $diet->delete();
        return back()->with('success', 'Diet entry deleted.');
    }
}
