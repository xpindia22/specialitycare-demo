<?php
// FILE: app/Http/Controllers/NursingTaskController.php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\NursingTask;
use Illuminate\Http\Request;

class NursingTaskController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $validated = $request->validate([
            'task'   => 'required|string|max:500',
            'due_at' => 'nullable|date',
        ]);

        $admission->nursingTasks()->create([
            'task'       => $validated['task'],
            'due_at'     => $validated['due_at'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Task added.');
    }

    public function complete(Admission $admission, NursingTask $task)
    {
        abort_if($task->admission_id !== $admission->id, 403);
        $task->update(['completed_at' => now()]);
        return back()->with('success', 'Task marked complete.');
    }

    public function destroy(Admission $admission, NursingTask $task)
    {
        abort_if($task->admission_id !== $admission->id, 403);
        $task->delete();
        return back()->with('success', 'Task deleted.');
    }
}
