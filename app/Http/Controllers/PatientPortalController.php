<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Patient-facing forum portal. Patients log in here with phone + the
 * desk-issued PIN (the `patient` guard), then jump to the phpBB forum where
 * SSO logs them into the restricted Patients area.
 *
 * Entirely separate from the staff HMS — patients never get a staff session.
 */
class PatientPortalController extends Controller
{
    public function __construct()
    {
        // Portal only exists where the forum module is licensed.
        if (!app(LicenseService::class)->isEnabled('forum')) {
            abort(404);
        }
    }

    public function showLogin()
    {
        if (Auth::guard('patient')->check()) {
            return redirect()->route('portal.home');
        }
        return view('portal.login', ['clinic' => $this->clinic()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'pin'   => 'required|string|max:32',
        ]);

        $ok = Auth::guard('patient')->attempt([
            'phone'          => trim($data['phone']),
            'portal_enabled' => true,
            'is_active'      => true,
            'password'       => $data['pin'],   // checked against portal_pin hash
        ]);

        if (!$ok) {
            return back()->withErrors(['phone' => 'Incorrect phone or PIN, or forum access is not enabled. Ask the clinic reception to set up forum access.'])
                ->onlyInput('phone');
        }

        $request->session()->regenerate();
        return redirect()->route('portal.home');
    }

    public function home()
    {
        return view('portal.home', [
            'patient' => Auth::guard('patient')->user(),
            'clinic'  => $this->clinic(),
            'forumUrl'=> url('/forum'),
        ]);
    }

    /** Patient's ongoing conversation with the clinic; marks it read. */
    public function messages()
    {
        $patient = Auth::guard('patient')->user();
        $conversation = \App\Models\PatientConversation::forPatient($patient);
        $messages = $conversation->messages()->with('user')->get();
        $conversation->forceFill(['patient_read_at' => now()])->save();

        return view('portal.messages', [
            'patient'      => $patient,
            'conversation' => $conversation,
            'messages'     => $messages,
            'clinic'       => $this->clinic(),
        ]);
    }

    /** Patient sends a message to the clinic; notifies staff by email (async). */
    public function sendMessage(Request $request)
    {
        $patient = Auth::guard('patient')->user();
        $data = $request->validate(['body' => 'required|string|min:1|max:10000']);

        $conversation = \App\Models\PatientConversation::forPatient($patient);
        \App\Models\PatientMessage::create([
            'patient_conversation_id' => $conversation->id,
            'sender'  => 'patient',
            'user_id' => null,
            'body'    => $data['body'],
        ]);
        $conversation->forceFill(['last_message_at' => now(), 'patient_read_at' => now()])->save();

        // Notify the clinic (async, non-blocking).
        $to = setting('clinic_email') ?: 'demo@specialitycare.co.in';
        try {
            dispatch(function () use ($to, $patient, $data) {
                try {
                    \Illuminate\Support\Facades\Mail::raw(
                        "Patient {$patient->full_name} ({$patient->jhc_id}) sent a message via the portal:\n\n"
                        . \Illuminate\Support\Str::limit($data['body'], 500)
                        . "\n\nReply from staff Messages → Patient messages.",
                        fn ($m) => $m->to($to)->subject('[' . setting('clinic_name', config('app.name')) . '] New patient message')
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('patient->clinic mail failed: ' . $e->getMessage());
                }
            })->afterResponse();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('patient->clinic dispatch failed: ' . $e->getMessage());
        }

        return redirect()->route('portal.messages')->withFragment('pm-bottom');
    }

    public function logout(Request $request)
    {
        Auth::guard('patient')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('portal.login')->with('success', 'You have been logged out.');
    }

    private function clinic(): array
    {
        return [
            'name'  => setting('clinic_name', config('app.name')),
            'phone' => setting('clinic_phone'),
        ];
    }
}
