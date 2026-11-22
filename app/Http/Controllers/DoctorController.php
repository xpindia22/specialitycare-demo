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
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
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
        $doctor = Doctor::create(array_merge($request->except(["password","_token","_method","extra_departments"]),["user_id"=>$user->id,"doctor_id"=>"DOC".str_pad($num,4,"0",STR_PAD_LEFT),"is_active"=>true,"show_online"=>$request->boolean("show_online", true)]));
        $doctor->departments()->sync(array_diff((array) $request->input("extra_departments", []), [$doctor->department_id]));
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
        $departments = Department::where("is_active",true)->ordered()->get();
        $doctor->load("schedules", "departments");
        $leaves = $doctor->leaves()->whereDate("to_date", ">=", today()->subDays(30))->get();
        // OPD rooms already in use (department rooms + any timetable), for the Room dropdown
        $rooms = Department::whereNotNull("room")->pluck("room")
            ->merge(DoctorSchedule::whereNotNull("room")->pluck("room"))
            ->map(fn ($r) => trim($r))->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
        return view("doctors.edit", compact("doctor","departments","leaves","rooms"));
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
            $request->except(["_token","_method","password","email","auto_present","show_online","extra_departments"]),
            ['auto_present' => $request->boolean('auto_present'), 'show_online' => $request->boolean('show_online')]
        ));
        $doctor->departments()->sync(array_diff((array) $request->input("extra_departments", []), [$doctor->department_id]));
        if ($request->filled("password")) { $doctor->user->update(["password"=>Hash::make($request->password)]); }
        $doctor->user->update(["name"=>$request->first_name." ".$request->last_name]);
        ActivityLog::log('updated', "Updated doctor {$doctor->first_name} {$doctor->last_name} ({$doctor->doctor_id})");
        return redirect()->route("doctors.index")->with("success","Doctor updated.");
    }

    /** Replace the doctor's weekly OPD timetable (rows: day, start, end, slot, max, dept, room). Empty = use the simple days/hours above. */
    public function updateSchedule(Request $request, Doctor $doctor) {
        $data = $request->validate([
            "rows"                 => "nullable|array|max:60",
            "rows.*.day_of_week"   => "required|integer|between:0,6",
            "rows.*.start_time"    => "required|date_format:H:i",
            "rows.*.end_time"      => "required|date_format:H:i|after:rows.*.start_time",
            "rows.*.slot_minutes"  => "nullable|integer|min:1|max:240",
            "rows.*.max_patients"  => "nullable|integer|min:1|max:500",
            "rows.*.department_id" => "nullable|exists:departments,id",
            "rows.*.room"          => "nullable|string|max:60",
        ], ["rows.*.end_time.after" => "Each session must end after it starts."]);
        $rows = collect($data["rows"] ?? []);
        foreach ($rows->groupBy("day_of_week") as $dow => $day) {
            $sorted = $day->sortBy("start_time")->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                if ($sorted[$i]["start_time"] < $sorted[$i - 1]["end_time"]) {
                    return back()->with("error", "Sessions overlap on " . DoctorSchedule::DAYS[$dow] . ". Fix the times and save again.");
                }
            }
        }
        \DB::transaction(function () use ($doctor, $rows) {
            $doctor->schedules()->delete();
            foreach ($rows as $r) $doctor->schedules()->create($r);
        });
        ActivityLog::log("updated", "Updated OPD timetable of Dr. {$doctor->first_name} {$doctor->last_name} ({$rows->count()} sessions)");
        return redirect()->route("doctors.edit", $doctor)->with("success", "Timetable saved.")->withFragment("timetable");
    }

    public function storeLeave(Request $request, Doctor $doctor) {
        $data = $request->validate([
            "from_date" => "required|date|after_or_equal:" . today()->subDays(30)->toDateString(),
            "to_date"   => "required|date|after_or_equal:from_date",
            "reason"    => "nullable|string|max:150",
        ]);
        $leave = $doctor->leaves()->create($data + ["created_by" => auth()->id()]);
        $booked = Appointment::where("doctor_id", $doctor->id)
            ->whereBetween("appointment_date", [$leave->from_date->toDateString(), $leave->to_date->toDateString()])
            ->whereIn("status", ["Scheduled", "Confirmed"])->count();
        ActivityLog::log("created", "Leave for Dr. {$doctor->first_name} {$doctor->last_name}: {$leave->from_date->toDateString()} to {$leave->to_date->toDateString()}" . ($leave->reason ? " ({$leave->reason})" : ""));
        $msg = "Leave added. Online booking and reception slots are closed for those days.";
        if ($booked) $msg .= " Note: {$booked} appointment(s) already booked in that period. Reschedule or cancel them from Appointments.";
        return redirect()->route("doctors.edit", $doctor)->with($booked ? "error" : "success", $msg)->withFragment("timetable");
    }

    public function destroyLeave(Doctor $doctor, DoctorLeave $leave) {
        abort_unless($leave->doctor_id === $doctor->id, 404);
        $leave->delete();
        ActivityLog::log("deleted", "Removed leave of Dr. {$doctor->first_name} {$doctor->last_name}: {$leave->from_date->toDateString()} to {$leave->to_date->toDateString()}");
        return redirect()->route("doctors.edit", $doctor)->with("success", "Leave removed.")->withFragment("timetable");
    }
}
