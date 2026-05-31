<?php

namespace App\Http\Controllers;

use App\Models\ConsentTemplate;
use App\Models\Patient;
use Illuminate\Http\Request;

class ConsentTemplateController extends Controller
{
    public function index()
    {
        $templates = ConsentTemplate::with('createdBy')->orderBy('name')->get();
        return view('consent-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('consent-templates.form', ['template' => new ConsentTemplate]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        ConsentTemplate::create($data + ['created_by' => auth()->id()]);
        return redirect()->route('consent-templates.index')->with('success', 'Consent template saved.');
    }

    public function edit(ConsentTemplate $consentTemplate)
    {
        return view('consent-templates.form', ['template' => $consentTemplate]);
    }

    public function update(Request $request, ConsentTemplate $consentTemplate)
    {
        $consentTemplate->update($this->validated($request));
        return redirect()->route('consent-templates.index')->with('success', 'Consent template updated.');
    }

    public function destroy(ConsentTemplate $consentTemplate)
    {
        $consentTemplate->update(['is_active' => false]);
        return back()->with('success', 'Consent template archived.');
    }

    /**
     * Generate a printable consent for a patient using a template.
     * Placeholders supported: {patient_name}, {jhc_id}, {age}, {gender},
     * {procedure}, {date}.
     */
    public function generate(Request $request, Patient $patient)
    {
        $request->validate([
            'template_id'    => 'required|exists:consent_templates,id',
            'procedure_name' => 'nullable|string|max:200',
        ]);

        $tpl = ConsentTemplate::findOrFail($request->template_id);
        $procedure = trim((string) $request->procedure_name) ?: '—';

        $body = strtr($tpl->body_html, [
            '{patient_name}' => e($patient->full_name),
            '{jhc_id}'       => e($patient->jhc_id ?? ''),
            '{age}'          => e($patient->age ?? ''),
            '{gender}'       => e($patient->gender ?? ''),
            '{procedure}'    => e($procedure),
            '{date}'         => now()->format('d M Y'),
        ]);

        return view('consent-templates.print', compact('patient', 'tpl', 'body', 'procedure'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'      => 'required|string|max:200',
            'category'  => 'nullable|in:' . implode(',', array_keys(ConsentTemplate::CATEGORIES)),
            'body_html' => 'required|string',
            'is_active' => 'boolean',
        ]);
    }
}
