<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    public function __construct(private readonly NotificationPreferenceService $prefs) {}

    public function index()
    {
        $events = NotificationPreference::orderByRaw("FIELD(group_label, 'Patient','OPD','Clinical','Lab','Reports','Billing','IPD')")
            ->orderBy('id')
            ->get()
            ->groupBy('group_label');

        return view('admin.notifications.preferences', compact('events'));
    }

    public function update(Request $request)
    {
        // Form posts one set of fields per event row. We toggle bool columns
        // for every event we know about — a missing checkbox = unchecked.
        $known = NotificationPreference::pluck('id')->all();
        foreach ($known as $id) {
            $row = $request->input("prefs.{$id}", []);
            NotificationPreference::where('id', $id)->update([
                'is_enabled'       => (bool) ($row['is_enabled']       ?? false),
                'patient_email'    => (bool) ($row['patient_email']    ?? false),
                'patient_whatsapp' => (bool) ($row['patient_whatsapp'] ?? false),
                'admin_email'      => (bool) ($row['admin_email']      ?? false),
                'admin_whatsapp'   => (bool) ($row['admin_whatsapp']   ?? false),
            ]);
        }
        $this->prefs->forget();

        return back()->with('status', 'Notification preferences saved.');
    }
}
