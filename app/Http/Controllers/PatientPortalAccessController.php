<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Staff/reception side of the patient forum portal.
 *
 * Reception enables forum access for a patient, which generates a 6-digit
 * PIN shown ONCE on screen (to read out / print). Only the bcrypt hash is
 * stored. Login id is the patient's phone; a phone may back only one
 * portal-enabled patient.
 */
class PatientPortalAccessController extends Controller
{
    public function enable(Patient $patient)
    {
        if (!$patient->phone) {
            return back()->with('error', 'This patient has no phone number — add one before enabling forum access.');
        }
        if ($clash = $this->phoneClash($patient)) {
            return back()->with('error', "Phone {$patient->phone} already has forum access under another patient ({$clash->full_name}, {$clash->jhc_id}). Disable that first.");
        }

        $pin = $this->generatePin();
        $patient->forceFill([
            'portal_enabled'    => true,
            'portal_pin'        => Hash::make($pin),
            'portal_pin_set_at' => now(),
        ])->save();

        ActivityLog::log('updated', "Enabled forum portal for patient {$patient->full_name} ({$patient->jhc_id})");

        // Flash the plaintext PIN ONCE so reception can hand it over. It is
        // never stored in plaintext and won't appear again.
        return back()->with('portal_pin', $pin)->with('portal_pin_patient', $patient->id)
            ->with('success', 'Forum access enabled. Give the patient the PIN below — it is shown only once.');
    }

    public function reset(Patient $patient)
    {
        if (!$patient->portal_enabled) {
            return back()->with('error', 'Forum access is not enabled for this patient.');
        }
        $pin = $this->generatePin();
        $patient->forceFill([
            'portal_pin'        => Hash::make($pin),
            'portal_pin_set_at' => now(),
        ])->save();

        ActivityLog::log('updated', "Reset forum PIN for patient {$patient->full_name} ({$patient->jhc_id})");

        return back()->with('portal_pin', $pin)->with('portal_pin_patient', $patient->id)
            ->with('success', 'New PIN generated — shown only once below.');
    }

    public function disable(Patient $patient)
    {
        $patient->forceFill([
            'portal_enabled'    => false,
            'portal_pin'        => null,
            'portal_pin_set_at' => null,
        ])->save();

        ActivityLog::log('updated', "Disabled forum portal for patient {$patient->full_name} ({$patient->jhc_id})");

        return back()->with('success', 'Forum access disabled for this patient.');
    }

    /** Another portal-enabled patient sharing this phone, if any. */
    private function phoneClash(Patient $patient): ?Patient
    {
        return Patient::where('phone', $patient->phone)
            ->where('portal_enabled', true)
            ->where('id', '!=', $patient->id)
            ->first();
    }

    /** 6-digit numeric PIN, easy to read out. Login throttling guards brute force. */
    private function generatePin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
