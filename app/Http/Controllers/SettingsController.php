<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceCharge;

class SettingsController extends Controller
{
    public function index() {
        $settings      = DB::table('settings')->get()->keyBy('key');
        $services      = ServiceCharge::orderBy('category')->orderBy('name')->get();
        $roomRents     = ServiceCharge::where('category', 'Room Rent')->orderBy('name')->get();
        $labTests      = \App\Models\LabTest::with('category')->orderBy('name')->get();
        $activeLabTests= \App\Models\LabTest::where('is_active', true)->orderBy('name')->get();
        $labCategories = \App\Models\LabCategory::orderBy('name')->get();
        $labPanels     = \App\Models\LabPanel::with('tests')->orderBy('name')->get();
        $doctors             = \App\Models\Doctor::where('is_active',true)->orderBy('first_name')->get();
        $departments         = \App\Models\Department::orderBy('name')->get();
        $allergensByCategory = \App\Models\Allergen::orderBy('category')->orderBy('sort_order')->orderBy('name')->get()->groupBy('category');
        $clinicalChipsByField = \App\Models\ClinicalChip::orderBy('field_key')->orderBy('sort_order')->orderBy('id')->get()->groupBy('field_key');
        return view('settings.index', compact('settings','services','roomRents','labTests','activeLabTests','labCategories','labPanels','doctors','departments','allergensByCategory','clinicalChipsByField'));
    }

    public function update(Request $request) {
        $allowed = [
            'clinic_name','clinic_address','clinic_phone','clinic_email',
            'clinic_reg_no','clinic_gst','clinic_website',
            'jhc_id_prefix','jhc_id_padding','currency','footer_text',
            'shift_morning_start','shift_morning_end',
            'shift_evening_start','shift_evening_end',
            'shift_night_start','shift_night_end',
            // Print header / footer controls (apply to clinical reports)
            'print_header_enabled','print_footer_enabled',
            'print_footer_services','print_footer_timings',
            // Backup notification throttle — per channel. The cron-driven
            // sql_backup_nvme.sh reads these directly from the settings table and
            // gates WhatsApp + email independently. (backup_notify_frequency is the
            // legacy single key, kept as a migration fallback in the bash script.)
            //   *_frequency: 'every' | '2h' | '3h' | '6h' | 'daily'
            //                | 'custom' | 'failure_only' | 'never'
            //   *_custom_hours: integer 1..168 (used when frequency = 'custom')
            'backup_notify_frequency',
            'backup_notify_whatsapp_frequency',
            'backup_notify_whatsapp_custom_hours',
            'backup_notify_email_frequency',
            'backup_notify_email_custom_hours',
        ];
        foreach ($request->input('settings', []) as $key => $value) {
            if (in_array($key, $allowed)) {
                if (str_ends_with($key, '_custom_hours')) {
                    $value = (string) max(1, min(168, (int) $value));
                }
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'group' => 'clinic', 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
        return back()->with('success', 'Settings saved successfully.');
    }

    public function updateServicePrice(Request $request, ServiceCharge $service) {
        $request->validate(['price' => 'required|numeric|min:0']);
        $service->update(['price' => $request->price]);
        return response()->json(['success' => true]);
    }
}
