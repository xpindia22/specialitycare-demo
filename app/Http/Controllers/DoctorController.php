<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\OpdToken;
use Illuminate\Support\Facades\Hash;

class DoctorController extends Controller {
    public function index() {
        $doctors = Doctor::with("user","department")->orderBy("first_name")->paginate(20);
        return view("doctors.index", compact("doctors"));
    }
    public function create() {
        $departments = Department::where("is_active",true)->orderBy("name")->get();
        return view("doctors.create", compact("departments"));
    }
    public function store(Request $request) {
        $request->validate(["first_name"=>"required","last_name"=>"required","email"=>"required|email|unique:users,email","phone"=>"required","specialization"=>"required","qualification"=>"required","registration_no"=>"required","gender"=>"required"]);
        $user = User::create(["name"=>$request->first_name." ".$request->last_name,"email"=>$request->email,"password"=>Hash::make($request->password ?? "Doctor@123"),"role"=>"doctor","is_active"=>true]);
        $doctorRoleId = \App\Models\Role::where('name','doctor')->value('id');
        if ($doctorRoleId) $user->roles()->sync([$doctorRoleId]);
        $last = Doctor::orderBy("id","desc")->first();
        $num = $last ? (int) filter_var($last->doctor_id, FILTER_SANITIZE_NUMBER_INT) + 1 : 1;
        $doctor = Doctor::create(array_merge($request->except(["password","_token","_method"]),["user_id"=>$user->id,"doctor_id"=>"DOC".str_pad($num,4,"0",STR_PAD_LEFT),"is_active"=>true]));
        ActivityLog::log('created', "Added new doctor {$doctor->first_name} {$doctor->last_name} ({$doctor->doctor_id})");
        return redirect()->route("doctors.index")->with("success","Doctor added.");
    }
    public function show(Doctor $doctor) {
        $doctor->load("user","department");

        $todayAppointments = $doctor->appointments()
            ->with('patient')
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get();

        $monthEncounters = \App\Models\Encounter::where('doctor_id', $doctor->id)
            ->whereMonth('encounter_date', now()->month)
            ->whereYear('encounter_date',  now()->year)
            ->count();

        $todayTokens = \App\Models\OpdToken::where('doctor_id', $doctor->id)
            ->whereDate('token_date', today())
            ->count();

        $todayDone = \App\Models\OpdToken::where('doctor_id', $doctor->id)
            ->whereDate('token_date', today())
            ->where('status', 'done')
            ->count();

        $recentEncounters = \App\Models\Encounter::with('patient')
            ->where('doctor_id', $doctor->id)
            ->orderByDesc('encounter_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view("doctors.show", compact(
            "doctor", "todayAppointments",
            "monthEncounters", "todayTokens", "todayDone", "recentEncounters"
        ));
    }
    public function edit(Doctor $doctor) {
        $departments = Department::where("is_active",true)->orderBy("name")->get();
        return view("doctors.edit", compact("doctor","departments"));
    }
    public function update(Request $request, Doctor $doctor) {
        // Only run the deactivation guard when the form actually submitted an
        // is_active value — the edit form omits the field today, so without
        // this gate every save was triggering a false "you have appointments"
        // error and rejecting the update.
        $deactivating = $request->has('is_active')
            && $doctor->is_active
            && !$request->boolean('is_active');
        if ($deactivating) {
            $futureCount = Appointment::where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', '>=', today())
                ->whereIn('status', ['Scheduled', 'Confirmed'])
                ->count();
            if ($futureCount > 0) {
                return back()->with('error', "Cannot deactivate Dr. {$doctor->first_name} {$doctor->last_name} — they have {$futureCount} upcoming appointment(s). Cancel or reassign those first.")->withInput();
            }
        }
        $doctor->update(array_merge(
            $request->except(["_token","_method","password","email","auto_present"]),
            ['auto_present' => $request->boolean('auto_present')]
        ));
        if ($request->filled("password")) { $doctor->user->update(["password"=>Hash::make($request->password)]); }
        $doctor->user->update(["name"=>$request->first_name." ".$request->last_name]);
        ActivityLog::log('updated', "Updated doctor {$doctor->first_name} {$doctor->last_name} ({$doctor->doctor_id})");
        return redirect()->route("doctors.index")->with("success","Doctor updated.");
    }
}