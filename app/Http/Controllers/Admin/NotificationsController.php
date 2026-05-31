<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationsController extends Controller
{
    public function __construct(private readonly WhatsAppService $wa) {}

    public function index(Request $request)
    {
        $logs = WhatsAppMessage::with('patient')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $provider = $this->wa->provider();

        if ($provider === 'meta') {
            $fromNumber = (string) config('services.whatsapp.meta.phone_number_id');
            $configured = (string) config('services.whatsapp.meta.token') !== ''
                       && (string) config('services.whatsapp.meta.phone_number_id') !== '';
            $sandbox    = false; // Meta has no sandbox join-code flow
        } elseif ($provider === 'gupshup') {
            $fromNumber = (string) config('services.whatsapp.gupshup.source');
            $configured = (string) config('services.whatsapp.gupshup.api_key') !== ''
                       && (string) config('services.whatsapp.gupshup.source') !== '';
            $sandbox    = false;
        } else {
            $fromNumber = (string) config('services.twilio.whatsapp_from');
            $configured = (string) config('services.twilio.sid') !== ''
                       && (string) config('services.twilio.token') !== ''
                       && (string) config('services.twilio.whatsapp_from') !== '';
            $sandbox    = (bool) config('services.twilio.sandbox');
        }

        return view('admin.notifications.whatsapp', [
            'logs'          => $logs,
            'provider'      => $provider,
            'fromNumber'    => $fromNumber,
            'sandbox'       => $sandbox,
            'configured'    => $configured,
            'sentToday'     => WhatsAppMessage::whereDate('sent_at', today())->where('status', 'sent')->count(),
            'failedToday'   => WhatsAppMessage::whereDate('created_at', today())->where('status', 'failed')->count(),
            'testRecipient' => (string) config('services.whatsapp.test_recipient'),
            'masterOff'     => setting('wa_master_off', '0') === '1',
        ]);
    }

    /**
     * Toggle the global WhatsApp kill switch — gates every send via
     * WhatsAppService::dispatch(). When ON, no patient/owner/CC WhatsApp
     * message goes out from the Laravel app regardless of per-event prefs.
     * Bash cron sends (backup, etc.) bypass this since they hit Gupshup
     * directly, not via WhatsAppService.
     */
    public function toggleMasterOff(Request $request)
    {
        $val = $request->has('master_off') ? '1' : '0';
        DB::table('settings')->updateOrInsert(
            ['key' => 'wa_master_off'],
            ['value' => $val, 'group' => 'notifications', 'updated_at' => now(), 'created_at' => now()]
        );
        $msg = $val === '1'
            ? 'All WhatsApp messages disabled. Per-event preferences are overridden until you re-enable.'
            : 'WhatsApp messages re-enabled. Per-event preferences now apply again.';
        return back()->with('success', $msg);
    }

    public function sendToPatient(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1500',
        ]);

        if (empty($patient->phone)) {
            return back()->withErrors(['whatsapp' => 'Patient has no phone number on file.']);
        }

        $result = $this->wa->sendToPatient($patient, $data['message'], 'manual', Auth::id());

        if ($result['ok'] ?? false) {
            return back()->with('status', 'WhatsApp sent to ' . $patient->full_name . '.');
        }

        $why = $result['skipped'] ?? $result['error'] ?? 'unknown error';
        return back()->withErrors(['whatsapp' => 'Send failed: ' . $why]);
    }

    public function sendTest(Request $request)
    {
        $data = $request->validate([
            'phone'      => 'required|string|max:32',
            'message'    => 'required|string|max:1500',
            'patient_id' => 'nullable|integer|exists:patients,id',
        ]);

        $patient = !empty($data['patient_id']) ? Patient::find($data['patient_id']) : null;

        $result = $this->wa->send(
            $data['phone'],
            $data['message'],
            'test',
            $patient,
            Auth::id(),
        );

        if ($result['ok'] ?? false) {
            return back()->with('status', 'WhatsApp message sent (' . ($result['provider'] ?? '?') . '). ID: ' . ($result['message_id'] ?? '—'));
        }

        $why = $result['skipped'] ?? $result['error'] ?? 'unknown error';
        return back()->withErrors(['whatsapp' => 'Send failed: ' . $why]);
    }
}
