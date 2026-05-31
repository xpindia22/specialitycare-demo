<?php
// FILE: app/Http/Controllers/EcgController.php
namespace App\Http\Controllers;

use App\Models\EcgRecord;
use App\Models\EchoRecord;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;

class EcgController extends Controller
{
    public function index()
    {
        $records = EcgRecord::with('patient', 'doctor')->latest('record_date')->paginate(20);
        return view('ecg.index', compact('records'));
    }

    public function create(Request $request)
    {
        $patient = $request->patient_id ? Patient::find($request->patient_id) : null;
        $doctors = Doctor::where('is_active', true)->get();
        $price   = \App\Models\ServiceCharge::where('name', 'ECG')->value('price') ?? 200;
        return view('ecg.create', compact('patient', 'doctors', 'price'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'doctor_id'      => 'required|exists:doctors,id',
            'encounter_id'   => 'nullable|exists:encounters,id',
            'record_date'    => 'required|date',
            'heart_rate'     => 'nullable|string',
            'rhythm'         => 'nullable|string',
            'pr_interval'    => 'nullable|string',
            'qrs_duration'   => 'nullable|string',
            'qt_qtc'         => 'nullable|string',
            'axis'           => 'nullable|string',
            'interpretation' => 'nullable|string',
            'price'          => 'required|numeric|min:0',
        ]);

        $validated['record_no']    = EcgRecord::generateNo();
        $validated['performed_by'] = auth()->id();

        if ($request->hasFile('ecg_file')) {
            $validated['ecg_file'] = $request->file('ecg_file')->store('ecg', 'public');
        }

        $record = EcgRecord::create($validated);
        return redirect()->route('ecg.show', $record)->with('success', 'ECG record saved.');
    }

    public function show(EcgRecord $ecg)
    {
        $ecg->load('patient', 'doctor', 'performedBy');
        return view('ecg.show', compact('ecg'));
    }

    public function update(Request $request, EcgRecord $ecg)
    {
        $validated = $request->validate([
            'heart_rate'     => 'nullable|string',
            'rhythm'         => 'nullable|string',
            'pr_interval'    => 'nullable|string',
            'qrs_duration'   => 'nullable|string',
            'qt_qtc'         => 'nullable|string',
            'axis'           => 'nullable|string',
            'interpretation' => 'nullable|string',
            'price'          => 'nullable|numeric|min:0',
        ]);
        $ecg->update($validated);
        return back()->with('success', 'ECG updated successfully.');
    }
}

// FILE: app/Http/Controllers/EchoController.php
class EchoController extends Controller
{
    public function index()
    {
        $records = EchoRecord::with('patient', 'doctor')->latest('record_date')->paginate(20);
        return view('echo.index', compact('records'));
    }

    public function create(Request $request)
    {
        $patient = $request->patient_id ? Patient::find($request->patient_id) : null;
        $doctors = Doctor::where('is_active', true)->get();
        $price   = \App\Models\ServiceCharge::where('name', 'Echocardiography')->value('price') ?? 1500;
        return view('echo.create', compact('patient', 'doctors', 'price'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'doctor_id'        => 'required|exists:doctors,id',
            'encounter_id'     => 'nullable|exists:encounters,id',
            'record_date'      => 'required|date',
            'lv_edd'           => 'nullable|string',
            'lv_esd'           => 'nullable|string',
            'ef_percent'       => 'nullable|string',
            'lvpw'             => 'nullable|string',
            'ivs'              => 'nullable|string',
            'la_size'          => 'nullable|string',
            'ao_root'          => 'nullable|string',
            'rv_dimension'     => 'nullable|string',
            'mitral_valve'     => 'nullable|string',
            'aortic_valve'     => 'nullable|string',
            'tricuspid_valve'  => 'nullable|string',
            'pulmonary_valve'  => 'nullable|string',
            'impression'       => 'nullable|string',
            'recommendations'  => 'nullable|string',
            'price'            => 'required|numeric|min:0',
        ]);

        $validated['record_no']    = EchoRecord::generateNo();
        $validated['performed_by'] = auth()->id();

        if ($request->hasFile('echo_file')) {
            $validated['echo_file'] = $request->file('echo_file')->store('echo', 'public');
        }

        $record = EchoRecord::create($validated);
        return redirect()->route('echo.show', $record)->with('success', 'Echo record saved.');
    }

    public function show(EchoRecord $echo)
    {
        $echo->load('patient', 'doctor');
        return view('echo.show', compact('echo'));
    }
}

// FILE: app/Http/Controllers/DoctorController.php
class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::with('department')->paginate(15);
        return view('doctors.index', compact('doctors'));
    }

    public function create()
    {
        $departments = \App\Models\Department::where('is_active', true)->get();
        return view('doctors.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'gender'           => 'required|in:Male,Female,Other',
            'date_of_birth'    => 'nullable|date',
            'phone'            => 'required|string|max:15',
            'email'            => 'required|email|unique:users,email',
            'specialization'   => 'required|string|max:100',
            'department_id'    => 'nullable|exists:departments,id',
            'qualification'    => 'required|string|max:255',
            'registration_no'  => 'required|string|max:100',
            'experience_years' => 'nullable|integer|min:0',
            'consultation_fee' => 'required|numeric|min:0',
            'available_days'   => 'nullable|array',
            'start_time'       => 'nullable|string',
            'end_time'         => 'nullable|string',
            'slot_duration'    => 'nullable|integer|min:5',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = \App\Models\User::create([
            'name'     => "Dr. {$validated['first_name']} {$validated['last_name']}",
            'email'    => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role'     => 'doctor',
        ]);

        $validated['user_id']   = $user->id;
        $validated['doctor_id'] = Doctor::generateDoctorId();
        $validated['available_days'] = json_encode($validated['available_days'] ?? []);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('doctors/photos', 'public');
        }

        $doctor = Doctor::create($validated);
        return redirect()->route('doctors.show', $doctor)->with('success', 'Doctor registered successfully.');
    }

    public function show(Doctor $doctor)
    {
        $doctor->load('department');
        $todayAppointments = $doctor->appointments()
            ->whereDate('appointment_date', today())
            ->with('patient')
            ->orderBy('appointment_time')
            ->get();
        return view('doctors.show', compact('doctor', 'todayAppointments'));
    }

    public function edit(Doctor $doctor)
    {
        $departments = \App\Models\Department::where('is_active', true)->get();
        return view('doctors.edit', compact('doctor', 'departments'));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'phone'            => 'required|string|max:15',
            'specialization'   => 'required|string|max:100',
            'department_id'    => 'nullable|exists:departments,id',
            'qualification'    => 'required|string|max:255',
            'consultation_fee' => 'required|numeric|min:0',
            'available_days'   => 'nullable|array',
            'start_time'       => 'nullable|string',
            'end_time'         => 'nullable|string',
            'slot_duration'    => 'nullable|integer|min:5',
        ]);

        if (!empty($validated['available_days'])) {
            $validated['available_days'] = json_encode($validated['available_days']);
        }

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('doctors/photos', 'public');
        }

        $doctor->update($validated);
        return redirect()->route('doctors.show', $doctor)->with('success', 'Doctor updated.');
    }
}

// FILE: app/Http/Controllers/StaffController.php
class StaffController extends Controller
{
    public function index()
    {
        $staff = \App\Models\Staff::paginate(20);
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        return view('staff.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'gender'       => 'required|in:Male,Female,Other',
            'date_of_birth'=> 'nullable|date',
            'phone'        => 'required|string|max:15',
            'email'        => 'required|email|unique:users,email',
            'designation'  => 'required|string|max:100',
            'department'   => 'nullable|string|max:100',
            'joining_date' => 'required|date',
            'qualification'=> 'nullable|string',
            'salary'       => 'nullable|numeric|min:0',
            'aadhar_no'    => 'nullable|string',
            'password'     => 'required|string|min:8|confirmed',
        ]);

        $user = \App\Models\User::create([
            'name'     => "{$validated['first_name']} {$validated['last_name']}",
            'email'    => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role'     => strtolower(str_replace(' ', '_', $validated['designation'])),
        ]);

        $validated['user_id']  = $user->id;
        $validated['staff_id'] = \App\Models\Staff::generateStaffId();

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('staff/photos', 'public');
        }

        \App\Models\Staff::create($validated);
        return redirect()->route('staff.index')->with('success', 'Staff registered successfully.');
    }

    public function edit(\App\Models\Staff $staff)
    {
        return view('staff.edit', compact('staff'));
    }

    public function update(Request $request, \App\Models\Staff $staff)
    {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'phone'        => 'required|string|max:15',
            'designation'  => 'required|string|max:100',
            'department'   => 'nullable|string|max:100',
            'salary'       => 'nullable|numeric|min:0',
        ]);
        $staff->update($validated);
        return redirect()->route('staff.index')->with('success', 'Staff updated.');
    }
}

// FILE: app/Http/Controllers/DashboardController.php
class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $stats = [
            'today_appointments'   => \App\Models\Appointment::whereDate('appointment_date', $today)->count(),
            'today_patients'       => \App\Models\Encounter::whereDate('encounter_date', $today)->distinct('patient_id')->count('patient_id'),
            'today_collection'     => \App\Models\Bill::whereDate('bill_date', $today)->sum('paid_amount'),
            'today_bills'          => \App\Models\Bill::whereDate('bill_date', $today)->count(),
            'pending_appointments' => \App\Models\Appointment::whereDate('appointment_date', $today)->whereIn('status', ['Scheduled','Confirmed','Checked-In'])->count(),
            'total_patients'       => \App\Models\Patient::count(),
            'active_doctors'       => \App\Models\Doctor::where('is_active', true)->count(),
            'pending_bills'        => \App\Models\Bill::whereIn('payment_status', ['Pending','Partial'])->count(),
            'pending_labs'         => \App\Models\LabOrder::whereIn('status', ['Ordered','Collected','Processing'])->count(),
            'low_stock'            => \App\Models\PharmacyStock::where('is_active', true)->whereColumn('quantity','<=','min_stock_level')->where('quantity','>',0)->count(),
        ];

        $todayAppointments = \App\Models\Appointment::with('patient', 'doctor')
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_time')
            ->get();

        $recentPatients = \App\Models\Patient::latest()->take(5)->get();

        $monthlyRevenue = \App\Models\Bill::selectRaw('MONTH(bill_date) as month, SUM(paid_amount) as total')
            ->whereYear('bill_date', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return view('dashboard', compact('stats', 'todayAppointments', 'recentPatients', 'monthlyRevenue'));
    }
}

// FILE: app/Http/Controllers/ImportController.php
class ImportController extends Controller
{
    public function index()
    {
        return view('import.index');
    }

    public function importOpenEMR(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            try {
                // Check if patient with openemr_pid already exists
                $existing = \App\Models\Patient::where('openemr_pid', $data['pid'] ?? null)->first();
                if ($existing) { $skipped++; continue; }

                \App\Models\Patient::create([
                    'jhc_id'        => \App\Models\Patient::generateJhcId(),
                    'openemr_pid'  => $data['pid'] ?? null,
                    'first_name'   => $data['fname'] ?? '',
                    'last_name'    => $data['lname'] ?? '',
                    'middle_name'  => $data['mname'] ?? null,
                    'gender'       => $this->mapGender($data['sex'] ?? ''),
                    'date_of_birth'=> $this->parseDate($data['DOB'] ?? ''),
                    'phone'        => $data['phone_home'] ?? $data['phone_cell'] ?? '',
                    'email'        => $data['email'] ?? null,
                    'address'      => trim(($data['street'] ?? '') . ' ' . ($data['city'] ?? '')),
                    'city'         => $data['city'] ?? null,
                    'state'        => $data['state'] ?? null,
                    'pincode'      => $data['postal_code'] ?? null,
                    'blood_group'  => $data['blood_type'] ?? null,
                    'occupation'   => $data['occupation'] ?? null,
                    'marital_status'=> $data['status'] ?? null,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row PID {$data['pid']}: " . $e->getMessage();
            }
        }
        fclose($handle);

        $msg = "Import complete. Imported: {$imported}, Skipped (existing): {$skipped}";
        if (!empty($errors)) {
            $msg .= ". Errors: " . count($errors);
            session()->flash('import_errors', array_slice($errors, 0, 10));
        }
        return back()->with('success', $msg);
    }

    private function mapGender(string $g): string
    {
        return match (strtolower($g)) {
            'm', 'male'   => 'Male',
            'f', 'female' => 'Female',
            default       => 'Unknown',
        };
    }

    private function parseDate(string $d): ?string
    {
        if (empty($d) || $d === '0000-00-00') return null;
        try { return \Carbon\Carbon::parse($d)->toDateString(); }
        catch (\Exception $e) { return null; }
    }
}

// FILE: app/Http/Controllers/SettingsController.php
class SettingsController extends Controller
{
    public function index()
    {
        $settings = \App\Models\Setting::all()->groupBy('group');
        $services = \App\Models\ServiceCharge::orderBy('category')->get();
        $labTests = \App\Models\LabTest::with('category')->get();
        return view('settings.index', compact('settings', 'services', 'labTests'));
    }

    public function update(Request $request)
    {
        foreach ($request->settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
        return back()->with('success', 'Settings saved successfully.');
    }

    public function updateServicePrice(Request $request, \App\Models\ServiceCharge $service)
    {
        $request->validate(['price' => 'required|numeric|min:0']);
        $service->update(['price' => $request->price]);
        return response()->json(['success' => true]);
    }
}
