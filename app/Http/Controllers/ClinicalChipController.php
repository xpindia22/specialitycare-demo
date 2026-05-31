<?php
namespace App\Http\Controllers;

use App\Models\ClinicalChip;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalChipController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'field_key' => ['required', Rule::in(array_keys(ClinicalChip::FIELDS))],
            'text'      => ['required', 'string', 'max:200'],
        ]);
        $data['text'] = trim($data['text']);

        $exists = ClinicalChip::where('field_key', $data['field_key'])
            ->whereRaw('LOWER(text) = ?', [mb_strtolower($data['text'])])
            ->exists();
        if ($exists) {
            return back()->with('settings_tab', 'chips')->withErrors(['chip_text' => 'That chip already exists in this field.']);
        }

        $maxSort = ClinicalChip::where('field_key', $data['field_key'])->max('sort_order') ?? 0;
        ClinicalChip::create([
            'field_key'  => $data['field_key'],
            'text'       => $data['text'],
            'sort_order' => $maxSort + 1,
            'is_active'  => true,
        ]);

        return back()->with('success', 'Chip added.')->with('settings_tab', 'chips');
    }

    public function update(Request $request, ClinicalChip $chip)
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:200'],
        ]);
        $data['text'] = trim($data['text']);

        $exists = ClinicalChip::where('field_key', $chip->field_key)
            ->whereKeyNot($chip->id)
            ->whereRaw('LOWER(text) = ?', [mb_strtolower($data['text'])])
            ->exists();
        if ($exists) {
            return back()->with('settings_tab', 'chips')->withErrors(['chip_text' => 'Another chip with that text already exists.']);
        }

        $chip->update(['text' => $data['text']]);
        return back()->with('success', 'Chip updated.')->with('settings_tab', 'chips');
    }

    public function destroy(ClinicalChip $chip)
    {
        $chip->delete();
        return back()->with('success', 'Chip removed.')->with('settings_tab', 'chips');
    }
}
