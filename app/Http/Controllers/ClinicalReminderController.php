<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use App\Services\ClinicalRuleEngine;
use Illuminate\Http\Request;

class ClinicalReminderController extends Controller
{
    /**
     * Live clinical reminders for the encounter screen.
     * Accepts the current (possibly unsaved) vitals + clinical text the doctor
     * is typing, merges them over the patient's demographics, and returns the
     * matching advisory suggestions as JSON.
     */
    public function evaluate(Request $request, Encounter $encounter, ClinicalRuleEngine $engine)
    {
        $encounter->loadMissing('patient');

        $input = $request->only(array_merge(
            \App\Models\ClinicalRule::VITAL_METRICS,
            ['age', 'gender',
             'chief_complaint', 'history_of_present_illness', 'past_medical_history',
             'family_history', 'social_history', 'examination_findings',
             'impression', 'plan', 'review_of_systems']
        ));

        // If the client sent no clinical input at all, fall back to the saved encounter.
        $hasInput = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $ctx = $hasInput
            ? $engine->contextFromInput($encounter->patient, $input)
            : $engine->contextFromEncounter($encounter);

        $suggestions = $engine->evaluate($ctx);

        return response()->json([
            'success'     => true,
            'count'       => count($suggestions),
            'suggestions' => $suggestions,
        ]);
    }
}
