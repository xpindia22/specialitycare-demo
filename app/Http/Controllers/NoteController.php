<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Note;

class NoteController extends Controller
{
    public function index() {
        $notes = Note::with('author')->latest()->paginate(20);
        return view('notes.index', compact('notes'));
    }

    public function create() {
        return view('notes.create');
    }

    public function store(Request $request) {
        $request->validate(['title' => 'required|string|max:255', 'body' => 'nullable|string']);
        $note = Note::create([
            'title'      => $request->title,
            'body'       => $request->body,
            'is_locked'  => false,
            'created_by' => auth()->id(),
        ]);
        ActivityLog::log('created', "Added note: \"{$note->title}\"");
        return redirect()->route('notes.index')->with('success', 'Note added.');
    }

    public function edit(Note $note) {
        abort_if($note->is_locked, 403, 'This note is locked and cannot be edited.');
        return view('notes.edit', compact('note'));
    }

    public function update(Request $request, Note $note) {
        abort_if($note->is_locked, 403, 'This note is locked.');
        $request->validate(['title' => 'required|string|max:255', 'body' => 'nullable|string']);
        $note->update($request->only('title', 'body'));
        ActivityLog::log('updated', "Updated note: \"{$note->title}\"");
        return redirect()->route('notes.index')->with('success', 'Note updated.');
    }

    public function destroy(Note $note) {
        abort_if($note->is_locked, 403, 'This note is locked and cannot be deleted.');
        ActivityLog::log('deleted', "Deleted note: \"{$note->title}\"");
        $note->delete();
        return redirect()->route('notes.index')->with('success', 'Note deleted.');
    }

    public function toggleLock(Note $note) {
        $note->update(['is_locked' => !$note->is_locked]);
        $action = $note->is_locked ? 'locked' : 'unlocked';
        ActivityLog::log($action, ucfirst($action) . " note: \"{$note->title}\"");
        return redirect()->route('notes.index')->with('success', 'Note ' . $action . '.');

    }
}
