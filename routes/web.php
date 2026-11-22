<?php
// FILE: routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\TriageController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\EncounterController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\EcgController;
use App\Http\Controllers\EchoController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\IpdNoteController;
use App\Http\Controllers\IpdBillingController;
use App\Http\Controllers\IpdMedicationController;
use App\Http\Controllers\IoEntryController;
use App\Http\Controllers\NursingShiftController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\IpdDashboardController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\RadiologyController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ChargeTemplateController;
use App\Http\Controllers\EncounterTemplateController;
use App\Http\Controllers\InsurerController;
use App\Http\Controllers\InsuranceClaimController;
use App\Http\Controllers\BillingPackageController;
use App\Http\Controllers\DrugInteractionController;
use App\Http\Controllers\NursingTaskController;
use App\Http\Controllers\DietChartController;
use App\Http\Controllers\PrescriptionTemplateController;
use App\Http\Controllers\PatientAllergyController;
use App\Http\Controllers\DietPrescriptionController;
use App\Http\Controllers\AllergenController;
use App\Http\Controllers\ClinicalChipController;
use App\Http\Controllers\HandoverNoteController;
use App\Http\Controllers\DutyRosterController;
use App\Http\Controllers\InsurancePreauthController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\Hr\AttendanceController as HrAttendanceController;
use App\Http\Controllers\Hr\PayrollAdjustmentController as HrAdjustmentController;
use App\Http\Controllers\Hr\PayrollController as HrPayrollController;

// Auth routes
Auth::routes(['register' => false, 'reset' => false]);

// Public TV Queue Display Board (no auth required)
Route::get('/queue-board', [OpdController::class, 'publicBoard'])->name('opd.public-board');

// Public online appointment booking (no auth required)
Route::get('/book',                      [\App\Http\Controllers\Public\BookingController::class, 'show'])->name('public.book.show');
Route::get('/book/slots', [\App\Http\Controllers\Public\BookingController::class, 'slots'])->middleware('throttle:60,1')->name('public.book.slots');
Route::post('/book', [\App\Http\Controllers\Public\BookingController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('public.book.store');
Route::get('/book/confirmed/{appointmentNo}', [\App\Http\Controllers\Public\BookingController::class, 'confirmed'])
    ->where('appointmentNo', '.*')
    ->name('public.book.confirmed');

Route::middleware(['auth'])->group(function () {

    // ── Dashboard (all roles) ───────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats',              [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/revenue-drilldown', [DashboardController::class, 'revenueDrilldown'])->name('dashboard.revenue-drilldown');

    // ── My Profile (all authenticated users — self-service password) ─────
    Route::get('/profile',           [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');

    // ── Admissions / IPD (gated by `ipd` module licence) ────────────────
    Route::middleware(['role:admin,doctor,nurse,receptionist', 'module:ipd'])->group(function () {
        Route::get('/admissions/beds', [AdmissionController::class, 'getBeds'])->name('admissions.beds');
        Route::get('/admissions/{admission}/draft-summary', [AdmissionController::class, 'draftDischargeSummary'])->name('admissions.draft-summary');
        Route::get('/admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])->name('admissions.discharge');
        Route::put('/admissions/{admission}/discharge', [AdmissionController::class, 'processDischarge'])->name('admissions.process-discharge');
        Route::get('/admissions/{admission}/summary', [AdmissionController::class, 'summary'])->name('admissions.summary');
        Route::patch('/admissions/{admission}/transfer', [AdmissionController::class, 'transfer'])->name('admissions.transfer');
        Route::post('/admissions/{admission}/checklist/{item}', [AdmissionController::class, 'clearItem'])->name('admissions.checklist.clear');
        Route::delete('/admissions/{admission}/checklist/{item}', [AdmissionController::class, 'unclearItem'])->name('admissions.checklist.unclear');
        Route::get('/admissions/{admission}/slip', [AdmissionController::class, 'slip'])->name('admissions.slip');
        Route::resource('admissions', AdmissionController::class)->except(['edit', 'update', 'destroy']);

        // IPD notes & vitals
        Route::post('/admissions/{admission}/notes',               [IpdNoteController::class, 'storeNote'])->name('admissions.notes.store');
        Route::delete('/admissions/{admission}/notes/{note}',      [IpdNoteController::class, 'destroyNote'])->name('admissions.notes.destroy');
        Route::post('/admissions/{admission}/vitals',              [IpdNoteController::class, 'storeVitals'])->name('admissions.vitals.store');
        Route::put('/admissions/{admission}/vitals/{vitals}',      [IpdNoteController::class, 'updateVitals'])->name('admissions.vitals.update');
        Route::delete('/admissions/{admission}/vitals/{vitals}',   [IpdNoteController::class, 'destroyVitals'])->name('admissions.vitals.destroy');

        // IPD billing
        Route::post('/admissions/{admission}/charges',             [IpdBillingController::class, 'store'])->name('admissions.charges.store');
        Route::delete('/admissions/{admission}/charges/{charge}',  [IpdBillingController::class, 'destroy'])->name('admissions.charges.destroy');
        Route::get('/admissions/{admission}/bill',                 [IpdBillingController::class, 'bill'])->name('admissions.bill');

        // IPD Deposits
        Route::post('/admissions/{admission}/deposits',            [IpdBillingController::class, 'storeDeposit'])->name('admissions.deposits.store');
        Route::delete('/admissions/{admission}/deposits/{deposit}', [IpdBillingController::class, 'destroyDeposit'])->name('admissions.deposits.destroy');

        // Discharge Summary PDF
        Route::get('/admissions/{admission}/summary-pdf',          [AdmissionController::class, 'summaryPdf'])->name('admissions.summary-pdf');
        Route::get('/admissions/{admission}/discharge-instructions', [AdmissionController::class, 'dischargeInstructions'])->name('admissions.discharge-instructions');
        Route::get('/admissions/{admission}/referral-letter',       [AdmissionController::class, 'referralLetter'])->name('admissions.referral-letter');

        // IPD Medications / MAR
        Route::post('/admissions/{admission}/prescriptions',                              [IpdMedicationController::class, 'store'])->name('admissions.prescriptions.store');
        Route::put('/admissions/{admission}/prescriptions/{prescription}',                [IpdMedicationController::class, 'update'])->name('admissions.prescriptions.update');
        Route::patch('/admissions/{admission}/prescriptions/{prescription}/stop',         [IpdMedicationController::class, 'stop'])->name('admissions.prescriptions.stop');
        Route::post('/admissions/{admission}/prescriptions/{prescription}/log',           [IpdMedicationController::class, 'logDose'])->name('admissions.prescriptions.log');
        Route::get('/admissions/{admission}/mar',                                         [IpdMedicationController::class, 'mar'])->name('admissions.mar');

        // Consent Forms
        Route::post('/admissions/{admission}/consents',              [ConsentController::class, 'store'])->name('admissions.consents.store');
        Route::delete('/admissions/{admission}/consents/{consent}',  [ConsentController::class, 'destroy'])->name('admissions.consents.destroy');
        Route::get('/admissions/{admission}/consents/{type}/print',  [ConsentController::class, 'print'])->name('admissions.consents.print');

        // Nursing Shifts
        Route::post('/admissions/{admission}/nursing',               [NursingShiftController::class, 'store'])->name('admissions.nursing.store');
        Route::delete('/admissions/{admission}/nursing/{shift}',     [NursingShiftController::class, 'destroy'])->name('admissions.nursing.destroy');
        Route::get('/admissions/{admission}/nursing-handover',       [NursingShiftController::class, 'handover'])->name('admissions.nursing-handover');

        // Nursing Tasks
        Route::post('/admissions/{admission}/tasks',                 [NursingTaskController::class, 'store'])->name('admissions.tasks.store');
        Route::patch('/admissions/{admission}/tasks/{task}/complete',[NursingTaskController::class, 'complete'])->name('admissions.tasks.complete');
        Route::delete('/admissions/{admission}/tasks/{task}',        [NursingTaskController::class, 'destroy'])->name('admissions.tasks.destroy');

        // Diet Charts
        Route::post('/admissions/{admission}/diet',                  [DietChartController::class, 'store'])->name('admissions.diet.store');
        Route::delete('/admissions/{admission}/diet/{diet}',         [DietChartController::class, 'destroy'])->name('admissions.diet.destroy');

        Route::get('/diet-prescriptions',                            [DietPrescriptionController::class, 'index'])->name('diet-prescriptions.index');
        Route::get('/diet-prescriptions/create',                     [DietPrescriptionController::class, 'create'])->name('diet-prescriptions.create');
        Route::post('/diet-prescriptions',                           [DietPrescriptionController::class, 'store'])->name('diet-prescriptions.store');
        Route::get('/diet-prescriptions/{dietPrescription}',         [DietPrescriptionController::class, 'show'])->name('diet-prescriptions.show');

        // I/O Chart
        Route::post('/admissions/{admission}/io',              [IoEntryController::class, 'store'])->name('admissions.io.store');
        Route::delete('/admissions/{admission}/io/{entry}',    [IoEntryController::class, 'destroy'])->name('admissions.io.destroy');
        Route::get('/admissions/{admission}/io-chart',         [IoEntryController::class, 'print'])->name('admissions.io-chart');
        Route::post('/admissions/{admission}/handover',              [HandoverNoteController::class, 'store'])->name('admissions.handover.store');
        Route::delete('/admissions/{admission}/handover/{note}',     [HandoverNoteController::class, 'destroy'])->name('admissions.handover.destroy');
        Route::post('/admissions/{admission}/preauth',               [InsurancePreauthController::class, 'store'])->name('admissions.preauth.store');
        Route::patch('/admissions/{admission}/preauth/{preauth}',    [InsurancePreauthController::class, 'update'])->name('admissions.preauth.update');
        Route::delete('/admissions/{admission}/preauth/{preauth}',   [InsurancePreauthController::class, 'destroy'])->name('admissions.preauth.destroy');

        // IPD Dashboard
        Route::get('/ipd', [IpdDashboardController::class, 'index'])->name('ipd.dashboard');
        Route::get('/ipd/census', [IpdDashboardController::class, 'census'])->name('ipd.census');
        Route::get('/ipd/census/panel', [IpdDashboardController::class, 'censusPanel'])->name('ipd.census.panel');
    });

    // ── ICU: board, patient chart, infusions, drug calculator ──────────
    Route::middleware(['role:admin,doctor,nurse', 'module:ipd'])->group(function () {
        Route::get('/icu',                                              [\App\Http\Controllers\IcuController::class, 'board'])->name('icu.board');
        Route::get('/icu/calculator',                                   [\App\Http\Controllers\IcuController::class, 'calculator'])->name('icu.calculator');
        Route::get('/icu/{admission}',                                  [\App\Http\Controllers\IcuController::class, 'chart'])->name('icu.chart');
        Route::post('/icu/{admission}/obs',                             [\App\Http\Controllers\IcuController::class, 'storeObs'])->name('icu.obs.store');
        Route::post('/icu/{admission}/infusions',                       [\App\Http\Controllers\IcuController::class, 'startInfusion'])->name('icu.infusions.start');
        Route::post('/icu/{admission}/infusions/{infusion}/change',     [\App\Http\Controllers\IcuController::class, 'changeInfusion'])->name('icu.infusions.change');
        Route::post('/icu/{admission}/infusions/{infusion}/stop',       [\App\Http\Controllers\IcuController::class, 'stopInfusion'])->name('icu.infusions.stop');
        Route::delete('/icu/{admission}/infusions/{infusion}',          [\App\Http\Controllers\IcuController::class, 'destroyInfusion'])->name('icu.infusions.destroy');
    });

    // ── Wards & Beds (gated by `ipd` module licence) ────────────────────
    Route::middleware(['role:admin,doctor,nurse,receptionist', 'module:ipd'])->group(function () {
        Route::get('/wards',             [WardController::class, 'index'])->name('wards.index');
        Route::get('/bed-board',         [WardController::class, 'bedBoard'])->name('wards.bed-board');
        Route::get('/wards/create',      [WardController::class, 'create'])->name('wards.create')->middleware('role:admin');
        Route::post('/wards',            [WardController::class, 'store'])->name('wards.store')->middleware('role:admin');
        Route::patch('/wards/beds/{bed}',         [WardController::class, 'updateBed'])->name('wards.beds.update');
        Route::patch('/wards/beds/{bed}/toggle',  [WardController::class, 'toggleBed'])->name('wards.beds.toggle')->middleware('role:admin');
        Route::delete('/wards/beds/{bed}',        [WardController::class, 'destroyBed'])->name('wards.beds.destroy')->middleware('role:admin');
        Route::get('/wards/{ward}',      [WardController::class, 'show'])->name('wards.show');
        Route::get('/wards/{ward}/edit', [WardController::class, 'edit'])->name('wards.edit')->middleware('role:admin');
        Route::put('/wards/{ward}',      [WardController::class, 'update'])->name('wards.update')->middleware('role:admin');
        Route::post('/wards/{ward}/beds',         [WardController::class, 'addBeds'])->name('wards.beds.add')->middleware('role:admin');
    });

    // ── Triage / Nurse Station (admin, doctor, nurse) ───────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        Route::get('/triage',                [TriageController::class, 'index'])->name('triage.index');
        Route::get('/triage/{token}/create', [TriageController::class, 'create'])->name('triage.create');
        Route::post('/triage/{token}',       [TriageController::class, 'store'])->name('triage.store');
        Route::get('/triage/{token}',        [TriageController::class, 'show'])->name('triage.show');
        Route::post('/triage/{token}/quick-vitals', [TriageController::class, 'quickVitals'])->name('triage.quick-vitals');
    });

    // ── OPD Queue (gated by `opd` module licence) ───────────────────────
    Route::middleware(['role:admin,doctor,nurse,receptionist', 'module:opd'])->group(function () {
        Route::get('/opd',               [OpdController::class, 'index'])->name('opd.index');
        Route::post('/opd',              [OpdController::class, 'store'])->name('opd.store');
        Route::patch('/opd/{token}/status', [OpdController::class, 'updateStatus'])->name('opd.status');
        Route::get('/opd/doctor',        [OpdController::class, 'doctorQueue'])->name('opd.doctor');
        Route::get('/opd/board',         [OpdController::class, 'board'])->name('opd.board');
        Route::get('/opd/queue-data',    [OpdController::class, 'queueData'])->name('opd.queue-data');
        Route::post('/opd/{token}/encounter', [OpdController::class, 'startEncounter'])->name('opd.start-encounter');
        Route::post('/opd/{token}/next',      [OpdController::class, 'nextPatient'])->name('opd.next-patient');
        Route::get('/opd/register',          [OpdController::class, 'register'])->name('opd.register');
        Route::get('/opd/directory',         [OpdController::class, 'directory'])->name('opd.directory');
        Route::get('/opd/fee-quote',         [\App\Http\Controllers\OpdFeeController::class, 'quote'])->name('opd.fee-quote');
        Route::get('/opd/referrals',                     [\App\Http\Controllers\OpdReferralController::class, 'index'])->name('opd.referrals.index');
        Route::post('/encounters/{encounter}/referrals', [\App\Http\Controllers\OpdReferralController::class, 'store'])->name('opd.referrals.store');
        Route::post('/opd/referrals/{referral}/token',   [\App\Http\Controllers\OpdReferralController::class, 'issueToken'])->name('opd.referrals.token');
        Route::post('/opd/referrals/{referral}/cancel',  [\App\Http\Controllers\OpdReferralController::class, 'cancel'])->name('opd.referrals.cancel');
        Route::get('/opd/referrals/{referral}/print',    [\App\Http\Controllers\OpdReferralController::class, 'print'])->name('opd.referrals.print');
        Route::get('/opd/{token}/slip',      [OpdController::class, 'slip'])->name('opd.slip');
    });

    // ── Patients & Appointments (admin, doctor, nurse, receptionist) ────
    Route::middleware('role:admin,doctor,nurse,receptionist')->group(function () {
        Route::get('/patients/search',       [PatientController::class, 'search'])->name('patients.search');
        Route::get('/search',                 [PatientController::class, 'globalSearch'])->name('global.search');
        Route::get('/patients/{patient}/quick-view', [PatientController::class, 'quickView'])->name('patients.quick-view');
        Route::get('/patients/{patient}/timeline', [PatientController::class, 'timeline'])->name('patients.timeline');
        Route::get('/patients/{patient}/certificate', [PatientController::class, 'certificate'])->name('patients.certificate');
        Route::post('/patients/{patient}/whatsapp', [\App\Http\Controllers\Admin\NotificationsController::class, 'sendToPatient'])
            ->middleware('module:notifications')
            ->name('patients.whatsapp.send');

        // Patient forum portal access (phone + desk-issued PIN). Gated by the
        // forum module so it only appears where the forum is licensed.
        Route::middleware('module:forum')->group(function () {
            Route::post('/patients/{patient}/portal/enable',  [\App\Http\Controllers\PatientPortalAccessController::class, 'enable'])->name('patients.portal.enable');
            Route::post('/patients/{patient}/portal/reset',   [\App\Http\Controllers\PatientPortalAccessController::class, 'reset'])->name('patients.portal.reset');
            Route::post('/patients/{patient}/portal/disable', [\App\Http\Controllers\PatientPortalAccessController::class, 'disable'])->name('patients.portal.disable');
        });

        Route::resource('patients', PatientController::class);
        Route::post('patients/{patient}/toggle-active', [PatientController::class, 'toggleActive'])->name('patients.toggle-active');
        Route::post('patients/{patient}/toggle-pin',    [PatientController::class, 'togglePin'])->name('patients.toggle-pin');

        Route::get('/appointments/slots', [AppointmentController::class, 'getSlots'])->name('appointments.slots');
        Route::post('/appointments/{appointment}/checkin', [AppointmentController::class, 'checkin'])->name('appointments.checkin');
        Route::post('/appointments/{appointment}/no-show', [AppointmentController::class, 'noShow'])->name('appointments.no-show');
        Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::get('/appointments/{appointment}/start-encounter', [AppointmentController::class, 'startEncounter'])->name('appointments.start-encounter');
        Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
        Route::get('/appointments/calendar/events', [AppointmentController::class, 'calendarEvents'])->name('appointments.calendar-events');
        Route::post('/appointments/{appointment}/move', [AppointmentController::class, 'move'])->name('appointments.move');
        Route::post('/appointments/{appointment}/follow-up', [AppointmentController::class, 'followUpFromCalendar'])->name('appointments.follow-up-from-calendar');
        Route::resource('appointments', AppointmentController::class);
        Route::get('/encounters/{encounter}/book-followup', [AppointmentController::class, 'bookFollowUp'])->name('appointments.book-followup');
        Route::post('/appointments/{appointment}/remind-email',    [AppointmentController::class, 'remindEmail'])->name('appointments.remind-email');
        Route::post('/appointments/{appointment}/remind-whatsapp', [AppointmentController::class, 'remindWhatsApp'])->name('appointments.remind-whatsapp');

        Route::get('/prescriptions/{prescription}/print', [EncounterController::class, 'printPrescription'])->name('prescriptions.print');

        // Patient Allergies
        Route::post('patients/{patient}/allergies', [PatientAllergyController::class, 'store'])->name('patients.allergies.store');
        Route::delete('patients/{patient}/allergies/{allergy}', [PatientAllergyController::class, 'destroy'])->name('patients.allergies.destroy');
        Route::post('patients/{patient}/communications', [PatientController::class, 'storeCommunication'])->name('patients.communications.store');
        Route::delete('patients/{patient}/communications/{communication}', [PatientController::class, 'destroyCommunication'])->name('patients.communications.destroy');

        // Allergen list management (Settings)
        Route::post('/allergens', [AllergenController::class, 'store'])->name('allergens.store');
        Route::delete('/allergens/{allergen}', [AllergenController::class, 'destroy'])->name('allergens.destroy');

        // Prescription Templates
        Route::get('/prescription-templates', [PrescriptionTemplateController::class, 'index'])->name('prescription-templates.index');
        Route::post('/prescription-templates', [PrescriptionTemplateController::class, 'store'])->name('prescription-templates.store');
        Route::put('/prescription-templates/{template}', [PrescriptionTemplateController::class, 'update'])->name('prescription-templates.update');
        Route::delete('/prescription-templates/{template}', [PrescriptionTemplateController::class, 'destroy'])->name('prescription-templates.destroy');
        Route::get('/prescription-templates/{template}/items', [PrescriptionTemplateController::class, 'items'])->name('prescription-templates.items');

        Route::resource('notes', NoteController::class)->except(['show']);
        Route::patch('/notes/{note}/toggle-lock', [NoteController::class, 'toggleLock'])->name('notes.toggle-lock');
    });

    // ── Encounters / Clinical (admin, doctor, nurse) ────────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        Route::get('/encounters', [EncounterController::class, 'index'])->name('encounters.index');
        Route::get('/deleted-visits', [EncounterController::class, 'deleted'])->name('encounters.deleted');
        Route::get('/encounters/{encounter}', [EncounterController::class, 'show'])->name('encounters.show')->withTrashed();
        Route::get('/encounters/{encounter}/last-visit', [EncounterController::class, 'lastVisit'])->name('encounters.last-visit');
        Route::post('/encounters/{encounter}/vitals', [EncounterController::class, 'updateVitals'])->name('encounters.vitals');
        Route::post('/encounters/{encounter}/clinical', [EncounterController::class, 'updateClinical'])->name('encounters.clinical');
        Route::post('/encounters/{encounter}/reminders', [\App\Http\Controllers\ClinicalReminderController::class, 'evaluate'])->name('encounters.reminders');
        Route::get('/encounters/{encounter}/clinical-print', [EncounterController::class, 'printClinical'])->name('encounters.clinical-print');
        Route::post('/encounters/{encounter}/diagnoses', [EncounterController::class, 'saveDiagnoses'])->name('encounters.diagnoses');
        Route::get('/encounters/{encounter}/suggest-investigations', [EncounterController::class, 'suggestInvestigations'])->name('encounters.suggest-investigations');
        Route::get('/api/icd-search', [EncounterController::class, 'icdSearch'])->name('icd.search');
        Route::post('/encounters/{encounter}/prescription', [EncounterController::class, 'savePrescription'])->name('encounters.prescription');
        Route::post('/rx-options', [EncounterController::class, 'storeRxOption'])->name('rx-options.store');
        Route::post('/rx-options/pin', [EncounterController::class, 'pinRxOption'])->name('rx-options.pin');
        Route::put('/encounters/{encounter}/prescriptions/{prescription}',             [EncounterController::class, 'updatePrescription'])->name('encounters.prescriptions.update');
        Route::delete('/encounters/{encounter}/prescriptions/{prescription}',          [EncounterController::class, 'deletePrescription'])->name('encounters.prescriptions.destroy');
        Route::post('/encounters/{encounter}/prescriptions/{prescription}/merge/{target}', [EncounterController::class, 'mergePrescriptions'])->name('encounters.prescriptions.merge');
        Route::put('/encounters/{encounter}/prescription-items/{item}',                [EncounterController::class, 'updatePrescriptionItem'])->name('encounters.items.update');
        Route::delete('/encounters/{encounter}/prescription-items/{item}',             [EncounterController::class, 'deletePrescriptionItem'])->name('encounters.items.destroy');
        Route::post('/encounters/{encounter}/notes',                               [EncounterController::class, 'storeNote'])->name('encounters.notes.store');
        Route::put('/encounters/{encounter}/notes/{note}',                         [EncounterController::class, 'updateNote'])->name('encounters.notes.update');
        Route::delete('/encounters/{encounter}/notes/{note}',                      [EncounterController::class, 'destroyNote'])->name('encounters.notes.destroy');
        Route::post('/encounters/{encounter}/reports', [EncounterController::class, 'uploadReport'])->name('encounters.reports.upload');
        Route::delete('/encounters/{encounter}/reports/{report}', [EncounterController::class, 'deleteReport'])->name('encounters.reports.delete');
        Route::post('/encounters/{encounter}/complete', [EncounterController::class, 'complete'])->name('encounters.complete');
        Route::post('/encounters/{encounter}/forms/{form}', [\App\Http\Controllers\EncounterFormController::class, 'save'])->name('encounters.forms.save');
        Route::get('/encounters/{encounter}/forms/{form}/print', [\App\Http\Controllers\EncounterFormController::class, 'print'])->name('encounters.forms.print');
        Route::delete('/encounters/{encounter}', [EncounterController::class, 'destroy'])->name('encounters.destroy');
        Route::post('/encounters/{encounter}/restore', [EncounterController::class, 'restore'])->name('encounters.restore')->withTrashed();
        Route::delete('/encounters/{encounter}/permanent', [EncounterController::class, 'forceDestroy'])->name('encounters.force-destroy')->withTrashed();
        Route::post('/encounters/{encounter}/ai-chat', [AiAssistantController::class, 'chat'])->name('encounters.ai-chat')->middleware('module:ai_assistant');
    });

    // ── Laboratory (admin, doctor, nurse, lab) ──────────────────────────
    // ── Lab (gated by `lab` module licence) ─────────────────────────────
    Route::middleware(['role:admin,doctor,nurse,lab', 'module:lab'])->group(function () {
        Route::get('/lab', [LabController::class, 'index'])->name('lab.index');
        Route::get('/lab/order/create', [LabController::class, 'createOrder'])->name('lab.create-order');
        Route::post('/lab/order', [LabController::class, 'storeOrder'])->name('lab.store-order');
        // Upload a printed/scanned lab report PDF -> parse -> review -> create order+results
        Route::get('/lab/report/upload', [LabController::class, 'uploadReportForm'])->name('lab.report.upload');
        Route::post('/lab/report/parse', [LabController::class, 'parseReport'])->name('lab.report.parse');
        // A stray GET to the POST-only parse URL (old bookmark / refresh of a pre-PRG
        // page) must not 405 — send it to the review if one is in progress, else upload.
        Route::get('/lab/report/parse', fn () => session('lab_report_review')
            ? redirect()->route('lab.report.review')
            : redirect()->route('lab.report.upload'));
        Route::get('/lab/report/review', [LabController::class, 'reviewReport'])->name('lab.report.review');
        Route::post('/lab/report/store', [LabController::class, 'storeReport'])->name('lab.report.store');
        // Pending lab reports (queued from WhatsApp/inbound) — match to a patient & confirm
        Route::get('/lab/pending', [LabController::class, 'pendingReports'])->name('lab.pending');
        Route::get('/lab/pending/{pending}/review', [LabController::class, 'reviewPending'])->name('lab.pending.review');
        Route::post('/lab/pending/{pending}/reject', [LabController::class, 'rejectPending'])->name('lab.pending.reject');
        // Encounters of a patient (JSON) — populate the "attach to visit" dropdown on report review
        Route::get('/lab/patient/{patient}/encounters', [LabController::class, 'patientEncounters'])->name('lab.patient-encounters');
        Route::get('/lab/order/{order}', [LabController::class, 'showOrder'])->name('lab.order');
        Route::post('/lab/order/{order}/status', [LabController::class, 'updateStatus'])->name('lab.update-status');
        Route::post('/lab/item/{item}/results', [LabController::class, 'saveResults'])->name('lab.save-results');
        Route::post('/lab/order/{order}/add-tests', [LabController::class, 'addTests'])->name('lab.add-tests');
        Route::get('/lab/order/{order}/print', [LabController::class, 'printReport'])->name('lab.print-report');
        Route::get('/lab/tests/{test}/info', [LabController::class, 'testInfo'])->name('lab.tests.info');
        Route::get('/lab/panels/{panel}/info', [LabController::class, 'panelInfo'])->name('lab.panels.info');
        // Pin/favourite a test or panel so it surfaces at the top of the picker.
        Route::post('/lab/pin/{type}/{id}', [LabController::class, 'togglePin'])
            ->whereIn('type', ['test','panel'])->whereNumber('id')->name('lab.pin');

        // Lab test & panel management (admin only)
        Route::middleware(['role:admin','restrict.admin.owner'])->group(function () {
            Route::get('/lab/tests', [LabController::class, 'tests'])->name('lab.tests');
            Route::post('/lab/tests', [LabController::class, 'storeTest'])->name('lab.tests.store');
            Route::put('/lab/tests/{test}', [LabController::class, 'updateTest'])->name('lab.tests.update');
            Route::patch('/lab/tests/{test}/price', [LabController::class, 'updateTestPrice'])->name('lab.tests.price');
            Route::post('/lab/tests/{test}/price', [LabController::class, 'updateTestPrice'])->name('lab.test-price');
            Route::post('/lab/tests/{test}/profit', [LabController::class, 'updateTestProfit'])->name('lab.tests.profit');
            Route::post('/lab/tests/{test}/normal', [LabController::class, 'updateTestNormalValues'])->name('lab.tests.normal');
            Route::patch('/lab/tests/{test}/toggle', [LabController::class, 'toggleTest'])->name('lab.tests.toggle');
            Route::post('/lab/tests/{test}/toggle', [LabController::class, 'toggleTest'])->name('lab.tests.toggle-post');
            Route::delete('/lab/tests/{test}', [LabController::class, 'destroyTest'])->name('lab.tests.destroy');
            Route::post('/lab/tests/bulk-price', [LabController::class, 'bulkUpdatePrice'])->name('lab.tests.bulk-price');
            Route::post('/lab/profit-bulk', [LabController::class, 'bulkSetProfit'])->name('lab.profit.bulk');
            Route::get('/lab/panels', [LabController::class, 'panels'])->name('lab.panels');
            Route::post('/lab/panels', [LabController::class, 'storePanel'])->name('lab.panels.store');
            Route::put('/lab/panels/{panel}', [LabController::class, 'updatePanel'])->name('lab.panels.update');
            Route::patch('/lab/panels/{panel}/price', [LabController::class, 'updatePanelPrice'])->name('lab.panels.price');
            Route::post('/lab/panels/{panel}/profit', [LabController::class, 'updatePanelProfit'])->name('lab.panels.profit');
            Route::patch('/lab/panels/{panel}/toggle', [LabController::class, 'togglePanel'])->name('lab.panels.toggle');
            Route::delete('/lab/panels/{panel}', [LabController::class, 'destroyPanel'])->name('lab.panels.destroy');
        });
    });

    // ── ECG (admin, doctor, nurse) ──────────────────────────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        Route::post('/ecg/read', [EcgController::class, 'read'])->name('ecg.read');
        Route::resource('ecg', EcgController::class);
        Route::delete('/ecg/{ecg}/file', [EcgController::class, 'destroyFile'])->name('ecg.file.destroy');
    });

    // ── Echo (admin, doctor, nurse) ─────────────────────────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        // Template library (declared before resource so /echo/templates is not eaten by /echo/{echo})
        Route::get('/echo/templates',                 [EchoController::class, 'templatesJson'])->name('echo.templates.index');
        Route::post('/echo/templates',                [EchoController::class, 'storeTemplate'])->name('echo.templates.store');
        Route::delete('/echo/templates/{template}',   [EchoController::class, 'destroyTemplate'])->name('echo.templates.destroy');

        Route::resource('echo', EchoController::class);
        Route::delete('/echo/{echo}/file', [EchoController::class, 'destroyFile'])->name('echo.file.destroy');
        Route::post('/echo/{echo}/send-report', [EchoController::class, 'sendReport'])->name('echo.send-report');
        Route::get('/echo/{echo}/print/{filename?}', [EchoController::class, 'print'])->where('filename', '.*\.pdf')->name('echo.print');
    });

    // ── Pharmacy (gated by `pharmacy` module licence) ───────────────────
    Route::middleware(['role:admin,doctor,nurse', 'module:pharmacy'])->group(function () {
        Route::get('/pharmacy', [PharmacyController::class, 'dashboard'])->name('pharmacy.dashboard');
        Route::get('/pharmacy/medicines', [PharmacyController::class, 'medicines'])->name('pharmacy.medicines');
        Route::post('/pharmacy/medicines', [PharmacyController::class, 'storeMedicine'])->name('pharmacy.medicines.store');
        Route::put('/pharmacy/medicines/{medicine}', [PharmacyController::class, 'updateMedicine'])->name('pharmacy.medicines.update');
        Route::post('/pharmacy/medicines/quick-create', [PharmacyController::class, 'quickStoreMedicine'])->name('pharmacy.medicines.quick-create');
        Route::get('/pharmacy/medicines/{medicine}/info', [PharmacyController::class, 'getMedicineInfo'])->name('pharmacy.medicine.info');
        Route::get('/pharmacy/stock', [PharmacyController::class, 'stock'])->name('pharmacy.stock');
        Route::post('/pharmacy/stock', [PharmacyController::class, 'addStock'])->name('pharmacy.stock.add');
        Route::put('/pharmacy/stock/{stock}', [PharmacyController::class, 'updateStock'])->name('pharmacy.stock.update');
        Route::get('/pharmacy/stock-register', [PharmacyController::class, 'stockRegister'])->name('pharmacy.stock-register');
        Route::get('/pharmacy/stock-management',         [PharmacyController::class, 'stockManagement'])->name('pharmacy.stock-management');
        Route::get('/pharmacy/expired',                  [PharmacyController::class, 'expiredStock'])->name('pharmacy.expired');
        Route::post('/pharmacy/expired/remove',          [PharmacyController::class, 'removeExpiredStock'])->name('pharmacy.expired.remove');
        Route::delete('/pharmacy/stock/{stock}/delete',  [PharmacyController::class, 'deleteStock'])->name('pharmacy.stock.delete');
        Route::get('/pharmacy/stock/bulk-remove',        [PharmacyController::class, 'bulkRemoveStockIndex'])->name('pharmacy.stock.bulk-remove');
        Route::post('/pharmacy/stock/bulk-remove',       [PharmacyController::class, 'bulkRemoveStock'])->name('pharmacy.stock.bulk-remove.do');
        Route::get('/pharmacy/stock/removal-history',    [PharmacyController::class, 'removalHistory'])->name('pharmacy.stock.removal-history');
        Route::post('/pharmacy/dispense/{prescription}', [PharmacyController::class, 'dispensePrescription'])->name('pharmacy.dispense');

        // Purchases (Stock Receipts from supplier → auto-posts Purchase voucher in Accounts)
        Route::get('/pharmacy/purchases',              [\App\Http\Controllers\Pharmacy\PharmacyPurchasesController::class, 'index'])->name('pharmacy.purchases.index');
        Route::get('/pharmacy/purchases/create',       [\App\Http\Controllers\Pharmacy\PharmacyPurchasesController::class, 'create'])->name('pharmacy.purchases.create');
        Route::post('/pharmacy/purchases',             [\App\Http\Controllers\Pharmacy\PharmacyPurchasesController::class, 'store'])->name('pharmacy.purchases.store');
        Route::get('/pharmacy/purchases/{purchase}',   [\App\Http\Controllers\Pharmacy\PharmacyPurchasesController::class, 'show'])->name('pharmacy.purchases.show');

        // Medicines: bulk fix HSN/GST (paginated table + auto-classify button)
        Route::get('/pharmacy/medicines/tax-fix',      [\App\Http\Controllers\Pharmacy\MedicineTaxFixController::class, 'index'])->name('pharmacy.medicines.tax-fix');
        Route::post('/pharmacy/medicines/tax-fix',     [\App\Http\Controllers\Pharmacy\MedicineTaxFixController::class, 'bulkSave'])->name('pharmacy.medicines.tax-fix.save');
        Route::post('/pharmacy/medicines/tax-fix/auto',[\App\Http\Controllers\Pharmacy\MedicineTaxFixController::class, 'autoClassify'])->name('pharmacy.medicines.tax-fix.auto');

        // Drug Interaction Database
        Route::get('/pharmacy/interactions',              [DrugInteractionController::class, 'index'])->name('pharmacy.interactions.index');
        Route::post('/pharmacy/interactions',             [DrugInteractionController::class, 'store'])->name('pharmacy.interactions.store');
        Route::delete('/pharmacy/interactions/{interaction}', [DrugInteractionController::class, 'destroy'])->name('pharmacy.interactions.destroy');
        Route::post('/pharmacy/check-interactions',       [DrugInteractionController::class, 'check'])->name('pharmacy.interactions.check');
    });

    // ── Billing (gated by `billing` module licence) ─────────────────────
    Route::middleware(['role:admin,receptionist,nurse', 'module:billing'])->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
        Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
        Route::get('/billing/daily/report', [BillingController::class, 'daily'])->name('billing.daily');
        Route::get('/billing/{bill}', [BillingController::class, 'show'])->name('billing.show');
        Route::post('/billing/{bill}/payment', [BillingController::class, 'addPayment'])->name('billing.payment');
        Route::post('/billing/{bill}/status', [BillingController::class, 'updateStatus'])->name('billing.status');
        Route::post('/billing/{bill}/date', [BillingController::class, 'updateBillDate'])->name('billing.date');
        Route::post('/billing/{bill}/payments/{payment}/date', [BillingController::class, 'updatePaymentDate'])->name('billing.payment-date');
        Route::delete('/billing/{bill}/payments/{payment}', [BillingController::class, 'destroyPayment'])->name('billing.payment.destroy');
        Route::delete('/billing/{bill}', [BillingController::class, 'destroy'])->name('billing.destroy');
        Route::get('/billing/{bill}/print', [BillingController::class, 'print'])->name('billing.print');
        Route::get('/billing/{bill}/pdf', [BillingController::class, 'pdf'])->name('billing.pdf');
    });

    // ── Reports (admin, doctor, nurse) ──────────────────────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        Route::get('/reports',                      [ReportsController::class, 'index'])->name('reports.index');
        Route::get('/reports/doctor-revenue',       [ReportsController::class, 'doctorRevenue'])->name('reports.doctor-revenue');
        Route::get('/reports/referral-performance', [ReportsController::class, 'referralPerformance'])->name('reports.referral-performance');
        Route::get('/reports/occupancy',            [ReportsController::class, 'occupancy'])->name('reports.occupancy');
        Route::get('/reports/day-end',              [ReportsController::class, 'dayEndReport'])->name('reports.day-end');
        Route::get('/reports/outstanding-dues',     [ReportsController::class, 'outstandingDues'])->name('reports.outstanding-dues');
        Route::get('/reports/follow-up',            [ReportsController::class, 'followUpTracker'])->name('reports.follow-up');
        Route::post('/reports/follow-up/{encounter}/remind-whatsapp', [ReportsController::class, 'followUpRemindWhatsApp'])->name('reports.follow-up.remind-whatsapp');
        Route::post('/reports/follow-up/{encounter}/remind-email',    [ReportsController::class, 'followUpRemindEmail'])->name('reports.follow-up.remind-email');
        Route::get('/reports/revenue',              [ReportsController::class, 'revenueDashboard'])->name('reports.revenue');
        Route::get('/reports/packages',             [ReportsController::class, 'packageUsage'])->name('reports.packages');
        Route::get('/reports/diagnoses',            [ReportsController::class, 'diagnosisAnalytics'])->name('reports.diagnoses');
        Route::get('/notifications/feed',           [\App\Http\Controllers\NotificationController::class, 'feed'])->name('notifications.feed');
        Route::get('/patients/{patient}/vaccinations',                 [\App\Http\Controllers\VaccinationController::class, 'index'])->name('vaccinations.index');
        Route::post('/patients/{patient}/vaccinations',                [\App\Http\Controllers\VaccinationController::class, 'store'])->name('vaccinations.store');
        Route::delete('/patients/{patient}/vaccinations/{vaccination}',[\App\Http\Controllers\VaccinationController::class, 'destroy'])->name('vaccinations.destroy');
        Route::get('/patients/{patient}/vaccinations/print',           [\App\Http\Controllers\VaccinationController::class, 'print'])->name('vaccinations.print');
        Route::get('/patients/{patient}/growth',                       [PatientController::class, 'growthCharts'])->name('patients.growth');

        // Diabetes diary — blood-sugar + medicine log with auto-analysis (no encounter needed)
        Route::get('/patients/{patient}/glucose-log',                  [\App\Http\Controllers\GlucoseLogController::class, 'index'])->name('glucose.index');
        Route::post('/patients/{patient}/glucose-log',                 [\App\Http\Controllers\GlucoseLogController::class, 'store'])->name('glucose.store');
        Route::put('/patients/{patient}/glucose-log/{glucoseLog}',     [\App\Http\Controllers\GlucoseLogController::class, 'update'])->name('glucose.update');
        Route::post('/patients/{patient}/glucose-log/{glucoseLog}/food-time', [\App\Http\Controllers\GlucoseLogController::class, 'updateFoodTime'])->name('glucose.food-time');
        Route::delete('/patients/{patient}/glucose-log/{glucoseLog}',  [\App\Http\Controllers\GlucoseLogController::class, 'destroy'])->name('glucose.destroy');
        Route::get('/patients/{patient}/glucose-log/print',            [\App\Http\Controllers\GlucoseLogController::class, 'print'])->name('glucose.print');

        // Blood-pressure diary — readings + medicines with auto-analysis (no encounter needed)
        Route::get('/patients/{patient}/bp-log',                       [\App\Http\Controllers\BpLogController::class, 'index'])->name('bp.index');
        Route::post('/patients/{patient}/bp-log',                      [\App\Http\Controllers\BpLogController::class, 'store'])->name('bp.store');
        Route::put('/patients/{patient}/bp-log/{bpLog}',               [\App\Http\Controllers\BpLogController::class, 'update'])->name('bp.update');
        Route::delete('/patients/{patient}/bp-log/{bpLog}',            [\App\Http\Controllers\BpLogController::class, 'destroy'])->name('bp.destroy');
        Route::get('/patients/{patient}/bp-log/print',                 [\App\Http\Controllers\BpLogController::class, 'print'])->name('bp.print');

        // Health tracker — weight / heart-rate / SpO2 / stress + diet & exercise (no encounter needed)
        Route::get('/patients/{patient}/health',                       [\App\Http\Controllers\HealthTrackerController::class, 'index'])->name('health.index');
        Route::post('/patients/{patient}/health/metric',               [\App\Http\Controllers\HealthTrackerController::class, 'storeMetric'])->name('health.metric.store');
        Route::post('/patients/{patient}/health/diet',                 [\App\Http\Controllers\HealthTrackerController::class, 'storeDiet'])->name('health.diet.store');
        Route::post('/patients/{patient}/health/exercise',             [\App\Http\Controllers\HealthTrackerController::class, 'storeExercise'])->name('health.exercise.store');
        Route::put   ('/patients/{patient}/health/metric/{healthMetric}',    [\App\Http\Controllers\HealthTrackerController::class, 'updateMetric'])->name('health.metric.update');
        Route::put   ('/patients/{patient}/health/diet/{dietLog}',           [\App\Http\Controllers\HealthTrackerController::class, 'updateDiet'])->name('health.diet.update');
        Route::delete('/patients/{patient}/health/metric/{healthMetric}',   [\App\Http\Controllers\HealthTrackerController::class, 'destroyMetric'])->name('health.metric.destroy');
        Route::delete('/patients/{patient}/health/diet/{dietLog}',          [\App\Http\Controllers\HealthTrackerController::class, 'destroyDiet'])->name('health.diet.destroy');
        Route::delete('/patients/{patient}/health/exercise/{exerciseLog}',  [\App\Http\Controllers\HealthTrackerController::class, 'destroyExercise'])->name('health.exercise.destroy');

        // AI medicine suggestions (advisory decision support) — admin/doctor only
        Route::post('/patients/{patient}/ai-suggestions',              [\App\Http\Controllers\AiSuggestionController::class, 'suggest'])->middleware('role:admin,doctor')->name('ai.suggest');

        // Consent templates (admin)
        Route::resource('consent-templates', \App\Http\Controllers\ConsentTemplateController::class)->except(['show']);
        Route::post('/patients/{patient}/consent/generate', [\App\Http\Controllers\ConsentTemplateController::class, 'generate'])->name('consent.generate');

        // Manual "send to patient" — email + WhatsApp, always available regardless
        // of the notification_preferences auto-send toggle (Admin → Notification
        // Preferences controls automatic sends only).
        Route::post('/prescriptions/{prescription}/email',    [\App\Http\Controllers\EmailDocumentController::class, 'emailPrescription'])->name('prescriptions.email');
        Route::post('/prescriptions/{prescription}/whatsapp', [\App\Http\Controllers\WhatsAppDocumentController::class, 'sendPrescription'])->name('prescriptions.whatsapp');
        Route::post('/bills/{bill}/email',                    [\App\Http\Controllers\EmailDocumentController::class, 'emailBill'])->name('bills.email');
        Route::post('/bills/{bill}/whatsapp',                 [\App\Http\Controllers\WhatsAppDocumentController::class, 'sendBill'])->name('bills.whatsapp');
        Route::post('/lab/order/{order}/email',               [\App\Http\Controllers\EmailDocumentController::class, 'emailLabReport'])->name('lab.email');
        Route::post('/lab/order/{order}/whatsapp',            [\App\Http\Controllers\WhatsAppDocumentController::class, 'sendLabReport'])->name('lab.whatsapp');
        Route::post('/echo/{echo}/email',                     [\App\Http\Controllers\EmailDocumentController::class, 'emailEchoReport'])->name('echo.email');
        Route::post('/echo/{echo}/whatsapp',                  [\App\Http\Controllers\WhatsAppDocumentController::class, 'sendEchoReport'])->name('echo.whatsapp');

        // Backup & export
        Route::get('/admin/backup',                    [\App\Http\Controllers\BackupController::class, 'index'])->name('admin.backup');
        Route::post('/admin/backup/run',               [\App\Http\Controllers\BackupController::class, 'runBackup'])->name('admin.backup.run');
        Route::get('/admin/backup/download/{name}',    [\App\Http\Controllers\BackupController::class, 'download'])->name('admin.backup.download');
        Route::post('/admin/backup/delete',            [\App\Http\Controllers\BackupController::class, 'deleteBackups'])->name('admin.backup.delete');
        Route::get('/admin/export/{table}.csv',        [\App\Http\Controllers\BackupController::class, 'exportCsv'])->name('admin.export.csv');
        Route::get('/reports/outstanding-dues/export', [ReportsController::class, 'exportOutstandingDues'])->name('reports.outstanding-dues.export');
        Route::get('/reports/follow-up/export',     [ReportsController::class, 'exportFollowUp'])->name('reports.follow-up.export');
        Route::get('/reports/day-end/export',       [ReportsController::class, 'exportDayEnd'])->name('reports.day-end.export');
    });

    // ── Radiology (admin, doctor, nurse) ────────────────────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        Route::get('/radiology',                 [RadiologyController::class, 'index'])->name('radiology.index');
        Route::get('/radiology/create',          [RadiologyController::class, 'create'])->name('radiology.create');
        Route::post('/radiology',                [RadiologyController::class, 'store'])->name('radiology.store');
        Route::get('/radiology/{order}',         [RadiologyController::class, 'show'])->name('radiology.show');
        Route::put('/radiology/{order}',         [RadiologyController::class, 'update'])->name('radiology.update');
        Route::get('/radiology/{order}/print',   [RadiologyController::class, 'printReport'])->name('radiology.print');
    });

    // ── Tools (admin, doctor, nurse) ────────────────────────────────────
    Route::middleware('role:admin,doctor,nurse')->prefix('tools')->name('tools.')->group(function () {
        Route::get('/duplicates',                 [PatientController::class, 'duplicates'])->name('duplicates');
        Route::post('/duplicates/{patient}/merge',[PatientController::class, 'merge'])->name('duplicates.merge');
    });

    // ── OT Register (admin, doctor, nurse) ──────────────────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        Route::get('/ot',                        [OperationController::class, 'index'])->name('ot.index');
        Route::get('/ot/create',                 [OperationController::class, 'create'])->name('ot.create');
        Route::post('/ot',                       [OperationController::class, 'store'])->name('ot.store');
        Route::get('/ot/print/register',         [OperationController::class, 'printRegister'])->name('ot.print');
        Route::get('/ot/{operation}',            [OperationController::class, 'show'])->name('ot.show');
        Route::put('/ot/{operation}',            [OperationController::class, 'update'])->name('ot.update');
    });

    // ── Referrals (admin only) ───────────────────────────────────────────
    Route::middleware(['role:admin','restrict.admin.owner'])->group(function () {
        Route::get('/referrals',               [ReferralController::class, 'index'])->name('referrals.index');
        Route::get('/referrals/dashboard',     [ReferralController::class, 'dashboard'])->name('referrals.dashboard');
        Route::get('/referrals/create',        [ReferralController::class, 'create'])->name('referrals.create');
        Route::post('/referrals',              [ReferralController::class, 'store'])->name('referrals.store');
        Route::post('/referrals/quick',        [ReferralController::class, 'quickStore'])->name('referrals.quick');
        Route::post('/referrals/payment',      [ReferralController::class, 'recordPayment'])->name('referrals.payment');
        Route::get('/referrals/{source}',      [ReferralController::class, 'show'])->name('referrals.show');
        Route::get('/referrals/{source}/edit', [ReferralController::class, 'edit'])->name('referrals.edit');
        Route::put('/referrals/{source}',      [ReferralController::class, 'update'])->name('referrals.update');
        Route::delete('/referrals/{source}',   [ReferralController::class, 'destroy'])->name('referrals.destroy');
    });

    // ── Charge Templates (admin, doctor, nurse) ─────────────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        Route::get('/charge-templates/list',        [ChargeTemplateController::class, 'list'])->name('charge-templates.list');
        Route::get('/charge-templates/{chargeTemplate}/items', [ChargeTemplateController::class, 'items'])->name('charge-templates.items');
        Route::resource('charge-templates', ChargeTemplateController::class)->except(['show']);
    });

    // ── Encounter Templates (admin, doctor, nurse) ───────────────────────
    Route::middleware('role:admin,doctor,nurse')->group(function () {
        Route::get('/encounter-templates/list',     [EncounterTemplateController::class, 'list'])->name('encounter-templates.list');
        Route::resource('encounter-templates', EncounterTemplateController::class)->except(['show']);
    });

    // ── Billing Packages (admin, receptionist, nurse) ───────────────────
    Route::middleware('role:admin,receptionist,nurse')->group(function () {
        Route::get('/packages/list',                  [BillingPackageController::class, 'apiList'])->name('packages.api');
        Route::get('/packages/{package}/items',       [BillingPackageController::class, 'apiItems'])->name('packages.api.items');
        Route::resource('packages', BillingPackageController::class)->except(['show']);
        Route::post('/admissions/{admission}/packages',            [BillingPackageController::class, 'assign'])->name('packages.assign');
        Route::post('/admissions/{admission}/packages/{ap}/status',[BillingPackageController::class, 'updatePackageStatus'])->name('packages.status');
        Route::delete('/admissions/{admission}/packages/{ap}',     [BillingPackageController::class, 'removePackage'])->name('packages.remove');
    });

    // ── Insurance / TPA (admin, receptionist, nurse) ────────────────────
    Route::middleware('role:admin,receptionist,nurse')->group(function () {
        Route::resource('insurers', InsurerController::class)->except(['show', 'destroy']);
        Route::delete('/insurers/{insurer}', [InsurerController::class, 'destroy'])->name('insurers.destroy');

        Route::get('/insurance/claims',                   [InsuranceClaimController::class, 'index'])->name('insurance.claims.index');
        Route::get('/insurance/claims/create',            [InsuranceClaimController::class, 'create'])->name('insurance.claims.create');
        Route::post('/insurance/claims',                  [InsuranceClaimController::class, 'store'])->name('insurance.claims.store');
        Route::get('/insurance/claims/{claim}',           [InsuranceClaimController::class, 'show'])->name('insurance.claims.show');
        Route::get('/insurance/claims/{claim}/edit',      [InsuranceClaimController::class, 'edit'])->name('insurance.claims.edit');
        Route::put('/insurance/claims/{claim}',           [InsuranceClaimController::class, 'update'])->name('insurance.claims.update');
        Route::post('/insurance/claims/{claim}/status',   [InsuranceClaimController::class, 'updateStatus'])->name('insurance.claims.status');
        Route::post('/insurance/claims/{claim}/documents', [InsuranceClaimController::class, 'uploadDocument'])->name('insurance.documents.upload');
        Route::delete('/insurance/claims/{claim}/documents/{document}', [InsuranceClaimController::class, 'deleteDocument'])->name('insurance.documents.delete');
    });

    // ── Administration (admin + nurse full access) ───────────────────────
    Route::middleware('role:admin,nurse')->group(function () {
        Route::post('/ipd/room-charges', [ReportsController::class, 'generateRoomCharges'])->name('ipd.room-charges');
        Route::resource('doctors', DoctorController::class);
        Route::post('/doctors/{doctor}/schedule', [DoctorController::class, 'updateSchedule'])->name('doctors.schedule');
        Route::post('/doctors/{doctor}/leaves', [DoctorController::class, 'storeLeave'])->name('doctors.leaves.store');
        Route::delete('/doctors/{doctor}/leaves/{leave}', [DoctorController::class, 'destroyLeave'])->name('doctors.leaves.destroy');
        Route::get('/departments', [\App\Http\Controllers\DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [\App\Http\Controllers\DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [\App\Http\Controllers\DepartmentController::class, 'update'])->name('departments.update');
        Route::post('/departments/{department}/toggle', [\App\Http\Controllers\DepartmentController::class, 'toggle'])->name('departments.toggle');
        Route::get('/opd/fee-rules', [\App\Http\Controllers\OpdFeeController::class, 'index'])->name('opd.fees.index');
        Route::post('/opd/fee-rules', [\App\Http\Controllers\OpdFeeController::class, 'storeRule'])->name('opd.fees.store');
        Route::put('/opd/fee-rules/{rule}', [\App\Http\Controllers\OpdFeeController::class, 'updateRule'])->name('opd.fees.update');
        Route::delete('/opd/fee-rules/{rule}', [\App\Http\Controllers\OpdFeeController::class, 'destroyRule'])->name('opd.fees.destroy');
        Route::post('/opd/discounts', [\App\Http\Controllers\OpdFeeController::class, 'storeDiscount'])->name('opd.discounts.store');
        Route::put('/opd/discounts/{discount}', [\App\Http\Controllers\OpdFeeController::class, 'updateDiscount'])->name('opd.discounts.update');
        Route::delete('/opd/discounts/{discount}', [\App\Http\Controllers\OpdFeeController::class, 'destroyDiscount'])->name('opd.discounts.destroy');
        // Duty roster routes MUST come before Route::resource('staff') —
        // otherwise staff/{staff} matches staff/duty-roster and routes to the
        // (non-existent) StaffController::show.
        Route::get('/staff/duty-roster',        [DutyRosterController::class, 'index'])->name('staff.duty-roster');
        Route::post('/staff/duty-roster',       [DutyRosterController::class, 'store'])->name('staff.duty-roster.store');
        Route::delete('/staff/duty-roster/{roster}', [DutyRosterController::class, 'destroy'])->name('staff.duty-roster.destroy');
        Route::resource('staff', StaffController::class)->except(['destroy', 'show']);
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

        // User / login-account management. Gated by admin.users so only
        // admins (not every nurse) can reset passwords and reassign roles.
        // Covers accounts that have no Staff record (incl. the owner admin).
        Route::middleware('permission:admin.users')->group(function () {
            Route::get('/users',                       [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}/edit',           [UserController::class, 'edit'])->name('users.edit');
            Route::post('/users/{user}/reset-password',[UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('/users/{user}/roles',         [UserController::class, 'updateRoles'])->name('users.roles');
            Route::post('/users/{user}/delete-visits', [UserController::class, 'updateDeleteVisits'])->name('users.delete-visits');
        });
        // Login activity log (IP + country) — owner-only (the controller
        // 403s any non-owner in its constructor).
        Route::get('/admin/login-activity', [\App\Http\Controllers\Admin\LoginActivityController::class, 'index'])
            ->name('admin.login-activity');
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/service/{service}/price', [SettingsController::class, 'updateServicePrice'])->name('settings.service-price');
        // Clinical quick-pick chips (Settings → Clinical Chips tab)
        Route::post('/clinical-chips',                  [ClinicalChipController::class, 'store'])->name('clinical-chips.store');
        Route::put('/clinical-chips/{chip}',            [ClinicalChipController::class, 'update'])->name('clinical-chips.update');
        Route::delete('/clinical-chips/{chip}',         [ClinicalChipController::class, 'destroy'])->name('clinical-chips.destroy');
        // Clinical reminder rules (Settings → Clinical Rules tab) — advisory CDS popup engine
        Route::post('/clinical-rules',                  [\App\Http\Controllers\ClinicalRuleController::class, 'store'])->name('clinical-rules.store');
        Route::put('/clinical-rules/{clinical_rule}',   [\App\Http\Controllers\ClinicalRuleController::class, 'update'])->name('clinical-rules.update');
        Route::post('/clinical-rules/{clinical_rule}/toggle', [\App\Http\Controllers\ClinicalRuleController::class, 'toggle'])->name('clinical-rules.toggle');
        Route::delete('/clinical-rules/{clinical_rule}', [\App\Http\Controllers\ClinicalRuleController::class, 'destroy'])->name('clinical-rules.destroy');
        Route::resource('certificates', CertificateController::class)->except(['edit', 'update']);
        Route::get('/certificates/{certificate}/print', [CertificateController::class, 'print'])->name('certificates.print');
    });

    // ── Modules + License management (admin) — buyers see what's active AND
    //    can install/replace the license without shell access. Status is
    //    auto-refreshed from the licensor on every GET (the controller calls
    //    sendIfDue(true) at the top of index()), so no explicit "heartbeat"
    //    button is needed — and an honest button wouldn't make the install
    //    safer anyway since a malicious buyer simply wouldn't click it.
    Route::middleware(['role:admin','restrict.admin.owner'])->prefix('admin/modules')->name('admin.modules.')->group(function () {
        Route::get('/',                       [\App\Http\Controllers\Admin\ModulesViewController::class, 'index'])->name('index');
        Route::post('/install-license',       [\App\Http\Controllers\Admin\ModulesViewController::class, 'installLicense'])->name('install-license');
    });

    // ── Notifications (WhatsApp/SMS) — admin, gated by `notifications` module ─
    Route::middleware(['permission:admin.notifications', 'module:notifications'])
        ->prefix('admin/notifications')
        ->name('admin.notifications.')
        ->group(function () {
            Route::get('/whatsapp',                  [\App\Http\Controllers\Admin\NotificationsController::class, 'index'])->name('whatsapp');
            Route::post('/whatsapp/test',            [\App\Http\Controllers\Admin\NotificationsController::class, 'sendTest'])->name('whatsapp.test');
            Route::post('/whatsapp/master-toggle',   [\App\Http\Controllers\Admin\NotificationsController::class, 'toggleMasterOff'])->name('whatsapp.master-toggle');
            Route::get('/preferences',    [\App\Http\Controllers\Admin\NotificationPreferencesController::class, 'index'])->name('preferences');
            Route::post('/preferences',   [\App\Http\Controllers\Admin\NotificationPreferencesController::class, 'update'])->name('preferences.update');
        });

    // ── Roles & Permissions (admin.roles permission) ────────────────────
    Route::middleware('permission:admin.roles')->prefix('admin/roles')->name('admin.roles.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Admin\RolesController::class, 'index'])->name('index');
        Route::get('/create',         [\App\Http\Controllers\Admin\RolesController::class, 'create'])->name('create');
        Route::post('/',              [\App\Http\Controllers\Admin\RolesController::class, 'store'])->name('store');
        Route::get('/{role}/edit',    [\App\Http\Controllers\Admin\RolesController::class, 'edit'])->name('edit');
        Route::put('/{role}',         [\App\Http\Controllers\Admin\RolesController::class, 'update'])->name('update');
        Route::delete('/{role}',      [\App\Http\Controllers\Admin\RolesController::class, 'destroy'])->name('destroy');
    });

    // ── HR & Payroll (admin only — gated by the `hr` module license) ────
    Route::middleware(['role:admin','restrict.admin.owner','module:hr'])->prefix('hr')->name('hr.')->group(function () {
        Route::get('/attendance',                 [HrAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance',                [HrAttendanceController::class, 'bulkStore'])->name('attendance.store');
        Route::get('/attendance/range',           [HrAttendanceController::class, 'range'])->name('attendance.range');
        Route::get('/attendance/month',           [HrAttendanceController::class, 'month'])->name('attendance.month');

        Route::get('/adjustments',                [HrAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::post('/adjustments',               [HrAdjustmentController::class, 'store'])->name('adjustments.store');
        Route::delete('/adjustments/{adjustment}',[HrAdjustmentController::class, 'destroy'])->name('adjustments.destroy');

        Route::get('/payroll',                    [HrPayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/{run}/mark-paid',   [HrPayrollController::class, 'markPaid'])->name('payroll.mark-paid');
        Route::post('/payroll/{run}/unpay',       [HrPayrollController::class, 'unpay'])->name('payroll.unpay');
        Route::get('/payroll/{run}/slip',         [HrPayrollController::class, 'slip'])->name('payroll.slip');
        Route::post('/payroll/deactivate-recurring', [HrPayrollController::class, 'deactivateRedundantRecurring'])->name('payroll.deactivate-recurring');
    });

    // ── Accounts & GST (admin — gated by the `accounts` module license) ─
    Route::middleware(['permission:accounts.manage', 'module:accounts'])
        ->prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/',                                  [\App\Http\Controllers\Accounts\AccountsController::class,       'index'])->name('index');

        // Ledgers
        Route::get('/ledgers',                           [\App\Http\Controllers\Accounts\LedgersController::class,        'index'])->name('ledgers.index');
        Route::get('/ledgers/create',                    [\App\Http\Controllers\Accounts\LedgersController::class,        'create'])->name('ledgers.create');
        Route::post('/ledgers',                          [\App\Http\Controllers\Accounts\LedgersController::class,        'store'])->name('ledgers.store');
        Route::get('/ledgers/{ledger}',                  [\App\Http\Controllers\Accounts\LedgersController::class,        'show'])->name('ledgers.show');
        Route::get('/ledgers/{ledger}/edit',             [\App\Http\Controllers\Accounts\LedgersController::class,        'edit'])->name('ledgers.edit');
        Route::put('/ledgers/{ledger}',                  [\App\Http\Controllers\Accounts\LedgersController::class,        'update'])->name('ledgers.update');
        Route::delete('/ledgers/{ledger}',               [\App\Http\Controllers\Accounts\LedgersController::class,        'destroy'])->name('ledgers.destroy');

        // Vouchers
        Route::get('/vouchers',                          [\App\Http\Controllers\Accounts\VouchersController::class,       'index'])->name('vouchers.index');
        Route::get('/vouchers/create/{typeCode?}',       [\App\Http\Controllers\Accounts\VouchersController::class,       'create'])->name('vouchers.create');
        Route::post('/vouchers',                         [\App\Http\Controllers\Accounts\VouchersController::class,       'store'])->name('vouchers.store');
        Route::get('/vouchers/{voucher}',                [\App\Http\Controllers\Accounts\VouchersController::class,       'show'])->name('vouchers.show');
        Route::get('/vouchers/{voucher}/print',          [\App\Http\Controllers\Accounts\VouchersController::class,       'print'])->name('vouchers.print');
        Route::post('/vouchers/{voucher}/cancel',        [\App\Http\Controllers\Accounts\VouchersController::class,       'cancel'])->name('vouchers.cancel');

        // Quick expense entry (the common case: rent / electricity / petrol etc.)
        Route::get('/expenses/quick',                    [\App\Http\Controllers\Accounts\ExpensesController::class,       'quick'])->name('expenses.quick');
        Route::post('/expenses/quick',                   [\App\Http\Controllers\Accounts\ExpensesController::class,       'storeQuick'])->name('expenses.store');

        // Staff & Payees (define-once, pay-by-dropdown)
        Route::get('/payees',                            [\App\Http\Controllers\Accounts\PayeesController::class,         'index'])->name('payees.index');
        Route::get('/payees/create',                     [\App\Http\Controllers\Accounts\PayeesController::class,         'create'])->name('payees.create');
        Route::post('/payees',                           [\App\Http\Controllers\Accounts\PayeesController::class,         'store'])->name('payees.store');
        Route::get('/payees/{payee}/edit',               [\App\Http\Controllers\Accounts\PayeesController::class,         'edit'])->name('payees.edit');
        Route::put('/payees/{payee}',                    [\App\Http\Controllers\Accounts\PayeesController::class,         'update'])->name('payees.update');
        Route::delete('/payees/{payee}',                 [\App\Http\Controllers\Accounts\PayeesController::class,         'destroy'])->name('payees.destroy');
        Route::get('/expenses/salary',                   [\App\Http\Controllers\Accounts\SalaryExpensesController::class, 'index'])->name('expenses.salary');
        Route::post('/expenses/salary',                  [\App\Http\Controllers\Accounts\SalaryExpensesController::class, 'store'])->name('expenses.salary.store');
        Route::get('/expenses/pay-supplier',             [\App\Http\Controllers\Accounts\SupplierPaymentsController::class, 'index'])->name('expenses.pay-supplier');
        Route::post('/expenses/pay-supplier',            [\App\Http\Controllers\Accounts\SupplierPaymentsController::class, 'store'])->name('expenses.pay-supplier.store');

        Route::get('/suppliers',                         [\App\Http\Controllers\Accounts\SuppliersController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create',                  [\App\Http\Controllers\Accounts\SuppliersController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers',                        [\App\Http\Controllers\Accounts\SuppliersController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit',         [\App\Http\Controllers\Accounts\SuppliersController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}',              [\App\Http\Controllers\Accounts\SuppliersController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}',           [\App\Http\Controllers\Accounts\SuppliersController::class, 'destroy'])->name('suppliers.destroy');

        // Recurring expenses
        Route::get('/recurring',                         [\App\Http\Controllers\Accounts\RecurringExpensesController::class, 'index'])->name('recurring.index');
        Route::post('/recurring',                        [\App\Http\Controllers\Accounts\RecurringExpensesController::class, 'store'])->name('recurring.store');
        Route::post('/recurring/run-due',                [\App\Http\Controllers\Accounts\RecurringExpensesController::class, 'runDue'])->name('recurring.run-due');
        Route::delete('/recurring/{recurring}',          [\App\Http\Controllers\Accounts\RecurringExpensesController::class, 'destroy'])->name('recurring.destroy');

        // Reports
        Route::get('/reports/day-book',                  [\App\Http\Controllers\Accounts\ReportsController::class,        'dayBook'])->name('reports.day-book');
        Route::get('/reports/trial-balance',             [\App\Http\Controllers\Accounts\ReportsController::class,        'trialBalance'])->name('reports.trial-balance');
        Route::get('/reports/profit-loss',               [\App\Http\Controllers\Accounts\ReportsController::class,        'profitLoss'])->name('reports.profit-loss');
        Route::get('/reports/balance-sheet',             [\App\Http\Controllers\Accounts\ReportsController::class,        'balanceSheet'])->name('reports.balance-sheet');
        Route::get('/reports/gstr-1',                    [\App\Http\Controllers\Accounts\GstReportsController::class,     'gstr1'])->name('reports.gstr1');
        Route::get('/reports/gstr-3b',                   [\App\Http\Controllers\Accounts\GstReportsController::class,     'gstr3b'])->name('reports.gstr3b');
        Route::get('/reports/hsn-summary',               [\App\Http\Controllers\Accounts\GstReportsController::class,     'hsnSummary'])->name('reports.hsn-summary');

        // Settings (clinic GSTIN, state, FY)
        Route::get('/settings',                          [\App\Http\Controllers\Accounts\SettingsController::class,       'edit'])->name('settings.edit');
        Route::put('/settings',                          [\App\Http\Controllers\Accounts\SettingsController::class,       'update'])->name('settings.update');
    });

    // ── Import & Referrals (admin only) ─────────────────────────────────
    Route::middleware(['role:admin','restrict.admin.owner'])->group(function () {
        Route::get('/import', [ImportController::class, 'index'])->name('import.index');
        Route::post('/import/upload-script', [ImportController::class, 'uploadScript'])->name('import.upload-script');
        Route::post('/import/run', [ImportController::class, 'runImport'])->name('import.run');
        Route::post('/import/openemr', [ImportController::class, 'importOpenEMR'])->name('import.openemr');

        // Export / migrate SpecialityCare -> OpenEMR (reverse of Import)
        Route::get('/export-openemr', [\App\Http\Controllers\ExportController::class, 'index'])->name('export.index');
        Route::post('/export-openemr/run', [\App\Http\Controllers\ExportController::class, 'runExport'])->name('export.run');
    });

    // ── Forum & Messaging (gated by `forum` module license) ─────────────
    Route::middleware(['module:forum'])->group(function () {

        // ── Private messages (DMs) — at /messages/dm/* ──────────────────
        // Registered BEFORE the forum boards so /messages/dm/* literals win
        // over the boards' /messages/{forum:slug} pattern. Route NAMES stay
        // `messages.*` so all route() links keep working.
        Route::middleware('permission:messages.send')->prefix('messages/dm')->name('messages.')->group(function () {
            Route::get('/',                       [\App\Http\Controllers\Forum\MessageController::class, 'inbox'])->name('inbox');
            Route::get('/unread-count',           [\App\Http\Controllers\Forum\MessageController::class, 'unreadCount'])->name('unread-count');
            Route::get('/compose',                [\App\Http\Controllers\Forum\MessageController::class, 'compose'])->name('compose');
            Route::post('/compose',               [\App\Http\Controllers\Forum\MessageController::class, 'storeCompose'])->name('compose.store');
            Route::get('/group/new',              [\App\Http\Controllers\Forum\MessageController::class, 'createGroupForm'])->name('group.create');
            Route::post('/group',                 [\App\Http\Controllers\Forum\MessageController::class, 'storeGroup'])->name('group.store');
            // Literal — must precede /{thread} so it isn't matched as a thread id.
            Route::get('/attachment/{attachment}', [\App\Http\Controllers\Forum\MessageController::class, 'downloadAttachment'])->name('attachment.show');
            // Literal routes — must precede /{thread} so they aren't matched as ids.
            Route::post('/bulk-delete',           [\App\Http\Controllers\Forum\MessageController::class, 'bulkDestroy'])->name('bulk-destroy');
            Route::get('/starred',                [\App\Http\Controllers\Forum\MessageController::class, 'starred'])->name('starred');
            // Patient ↔ clinic messaging (shared staff inbox).
            Route::get('/patients',               [\App\Http\Controllers\Forum\MessageController::class, 'patientInbox'])->name('patients.inbox');
            Route::get('/patients/{patient}',     [\App\Http\Controllers\Forum\MessageController::class, 'patientThread'])->name('patients.thread');
            Route::post('/patients/{patient}/send', [\App\Http\Controllers\Forum\MessageController::class, 'patientReply'])->name('patients.send');
            Route::get('/broadcast',              [\App\Http\Controllers\Forum\MessageController::class, 'broadcastForm'])->name('broadcast');
            Route::post('/broadcast',             [\App\Http\Controllers\Forum\MessageController::class, 'storeBroadcast'])->name('broadcast.store');
            Route::get('/{thread}',               [\App\Http\Controllers\Forum\MessageController::class, 'thread'])->name('thread');
            Route::get('/{thread}/poll',          [\App\Http\Controllers\Forum\MessageController::class, 'poll'])->name('poll');
            Route::delete('/{thread}',            [\App\Http\Controllers\Forum\MessageController::class, 'destroyThread'])->name('thread.destroy');
            Route::post('/{thread}/archive',      [\App\Http\Controllers\Forum\MessageController::class, 'toggleArchive'])->name('archive');
            Route::post('/{thread}/m/{message}/star', [\App\Http\Controllers\Forum\MessageController::class, 'toggleStar'])->name('star');
            Route::post('/{thread}/send',         [\App\Http\Controllers\Forum\MessageController::class, 'send'])->name('send');
            Route::post('/{thread}/leave',        [\App\Http\Controllers\Forum\MessageController::class, 'leave'])->name('leave');
            Route::post('/{thread}/mute',         [\App\Http\Controllers\Forum\MessageController::class, 'toggleMute'])->name('mute');
            Route::post('/{thread}/m/{message}/react',  [\App\Http\Controllers\Forum\MessageController::class, 'react'])->name('react');
            Route::post('/{thread}/m/{message}/unsend', [\App\Http\Controllers\Forum\MessageController::class, 'unsend'])->name('unsend');
            Route::post('/{thread}/m/{message}/edit',   [\App\Http\Controllers\Forum\MessageController::class, 'editMessage'])->name('edit');
            Route::post('/{thread}/patient',      [\App\Http\Controllers\Forum\MessageController::class, 'linkPatient'])->name('patient');
            Route::post('/{thread}/patient/{patient}/unlink', [\App\Http\Controllers\Forum\MessageController::class, 'unlinkPatient'])->name('patient.unlink');
        });

        // ── Native forum boards — at /messages, admin /messages/admin ───
        // (phpBB now owns /forum, so the in-built boards live under /messages.)
        // Route NAMES stay `forum.*` so all route() links keep working.
        Route::middleware('permission:forum.view')->prefix('messages')->name('forum.')->group(function () {
            Route::get('/',                        [\App\Http\Controllers\Forum\ForumController::class, 'index'])->name('index');

            // Admin CRUD comes FIRST so /messages/admin isn't swallowed by /messages/{forum:slug}.
            Route::middleware('permission:forum.admin')->prefix('admin')->name('admin.')->group(function () {
                Route::get('/',                             [\App\Http\Controllers\Forum\ForumAdminController::class, 'index'])->name('index');
                Route::post('/categories',                  [\App\Http\Controllers\Forum\ForumAdminController::class, 'storeCategory'])->name('categories.store');
                Route::put('/categories/{category}',        [\App\Http\Controllers\Forum\ForumAdminController::class, 'updateCategory'])->name('categories.update');
                Route::delete('/categories/{category}',     [\App\Http\Controllers\Forum\ForumAdminController::class, 'destroyCategory'])->name('categories.destroy');
                Route::get('/categories/{category}/forums/create', [\App\Http\Controllers\Forum\ForumAdminController::class, 'createForum'])->name('forums.create');
                Route::post('/categories/{category}/forums',       [\App\Http\Controllers\Forum\ForumAdminController::class, 'storeForum'])->name('forums.store');
                Route::get('/forums/{forum}/edit',          [\App\Http\Controllers\Forum\ForumAdminController::class, 'editForum'])->name('forums.edit');
                Route::put('/forums/{forum}',               [\App\Http\Controllers\Forum\ForumAdminController::class, 'updateForum'])->name('forums.update');
                Route::delete('/forums/{forum}',            [\App\Http\Controllers\Forum\ForumAdminController::class, 'destroyForum'])->name('forums.destroy');
            });

            // Private-forum password gate — literal segments, registered
            // BEFORE /{forum}/{topic} so they aren't swallowed by it.
            Route::get('/{forum:slug}/unlock',              [\App\Http\Controllers\Forum\ForumController::class, 'unlockForm'])->name('unlock');
            Route::post('/{forum:slug}/unlock',             [\App\Http\Controllers\Forum\ForumController::class, 'unlock'])->name('unlock.submit');
            Route::post('/{forum:slug}/lock',               [\App\Http\Controllers\Forum\ForumController::class, 'lock'])->name('lock');

            Route::get('/{forum:slug}',                     [\App\Http\Controllers\Forum\ForumController::class, 'show'])->name('show');
            Route::middleware('permission:forum.post')->group(function () {
                Route::get('/{forum:slug}/new',             [\App\Http\Controllers\Forum\ForumController::class, 'createTopic'])->name('topic.create');
                Route::post('/{forum:slug}/new',            [\App\Http\Controllers\Forum\ForumController::class, 'storeTopic'])->name('topic.store');
            });
            Route::get('/{forum:slug}/{topic:slug}',        [\App\Http\Controllers\Forum\ForumController::class, 'topic'])->name('topic');
            Route::post('/{forum:slug}/{topic:slug}/reply', [\App\Http\Controllers\Forum\ForumController::class, 'storeReply'])->middleware('permission:forum.reply')->name('reply');
            Route::get('/{forum:slug}/{topic:slug}/posts/{post}/edit',  [\App\Http\Controllers\Forum\ForumController::class, 'editPost'])->name('post.edit');
            Route::put('/{forum:slug}/{topic:slug}/posts/{post}',       [\App\Http\Controllers\Forum\ForumController::class, 'updatePost'])->name('post.update');
            Route::delete('/{forum:slug}/{topic:slug}/posts/{post}',    [\App\Http\Controllers\Forum\ForumController::class, 'destroyPost'])->name('post.destroy');
            Route::post('/{forum:slug}/{topic:slug}/flag',  [\App\Http\Controllers\Forum\ForumController::class, 'toggleFlag'])->name('topic.flag');
            Route::post('/{forum:slug}/{topic:slug}/subscribe', [\App\Http\Controllers\Forum\ForumController::class, 'toggleSubscribe'])->name('topic.subscribe');
        });
    });

});

// ── Public patient announcements (no auth; module-gated inside ctrl) ────
Route::get('/announcements',           [\App\Http\Controllers\Forum\PublicAnnouncementsController::class, 'index'])->name('public.announcements');
Route::get('/announcements/{topic:slug}', [\App\Http\Controllers\Forum\PublicAnnouncementsController::class, 'show'])->name('public.announcements.show');

// ── Patient forum portal (phone + desk-issued PIN; `patient` guard) ─────
Route::get('/portal/login',  [\App\Http\Controllers\PatientPortalController::class, 'showLogin'])->name('portal.login');
Route::post('/portal/login', [\App\Http\Controllers\PatientPortalController::class, 'login'])->middleware('throttle:10,1')->name('portal.login.post');
Route::middleware('auth:patient')->group(function () {
    Route::get('/portal',         [\App\Http\Controllers\PatientPortalController::class, 'home'])->name('portal.home');
    Route::get('/portal/messages',  [\App\Http\Controllers\PatientPortalController::class, 'messages'])->name('portal.messages');
    Route::post('/portal/messages', [\App\Http\Controllers\PatientPortalController::class, 'sendMessage'])->middleware('throttle:30,1')->name('portal.messages.send');
    Route::post('/portal/logout', [\App\Http\Controllers\PatientPortalController::class, 'logout'])->name('portal.logout');
});

/*
|--------------------------------------------------------------------------
| Owner Panel — module licensing (NOT inside auth group)
|--------------------------------------------------------------------------
| Gated by EnsureOwner middleware which 404s if OWNER_KEY env is unset
| or the session flag isn't set. So on a buyer install with no OWNER_KEY,
| every URL below returns 404 — the panel effectively does not exist.
*/
Route::prefix('owner')->name('owner.')->group(function () {
    // Bare /owner and /owner/ — landing redirects to login (or onward if
    // already authed — the login form's controller handles that).
    Route::get('/', fn () => redirect()->route('owner.login'));
    Route::get('/login',  [\App\Http\Controllers\Owner\ModulesController::class, 'loginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Owner\ModulesController::class, 'login'])->name('login.post');

    Route::middleware('owner')->group(function () {
        Route::get('/modules',         [\App\Http\Controllers\Owner\ModulesController::class, 'index'])->name('modules.index');
        Route::post('/modules/sign',   [\App\Http\Controllers\Owner\ModulesController::class, 'sign'])->name('modules.sign');
        Route::post('/modules/upload', [\App\Http\Controllers\Owner\ModulesController::class, 'upload'])->name('modules.upload');
        Route::post('/logout',         [\App\Http\Controllers\Owner\ModulesController::class, 'logout'])->name('logout');

        // Buyer ledger
        Route::resource('buyers', \App\Http\Controllers\Owner\BuyersController::class)->except(['destroy']);
        Route::post('/buyers/{buyer}/regenerate-keypair', [\App\Http\Controllers\Owner\BuyersController::class, 'regenerateKeypair'])->name('buyers.regenerate-keypair');

        // Issued licenses
        Route::get('/licenses',                       [\App\Http\Controllers\Owner\LicensesController::class, 'index'])->name('licenses.index');
        Route::get('/licenses/create/{buyer?}',       [\App\Http\Controllers\Owner\LicensesController::class, 'create'])->name('licenses.create');
        Route::post('/licenses',                      [\App\Http\Controllers\Owner\LicensesController::class, 'store'])->name('licenses.store');
        Route::get('/licenses/{license}',             [\App\Http\Controllers\Owner\LicensesController::class, 'show'])->name('licenses.show');
        Route::get('/licenses/{license}/download',    [\App\Http\Controllers\Owner\LicensesController::class, 'download'])->name('licenses.download');
        Route::post('/licenses/{license}/install',    [\App\Http\Controllers\Owner\LicensesController::class, 'installLocally'])->name('licenses.install');
        Route::post('/licenses/{license}/revoke',     [\App\Http\Controllers\Owner\LicensesController::class, 'revoke'])->name('licenses.revoke');
        Route::post('/licenses/{license}/unrevoke',   [\App\Http\Controllers\Owner\LicensesController::class, 'unrevoke'])->name('licenses.unrevoke');

        // Heartbeat dashboard — clone detection
        Route::get('/heartbeats', [\App\Http\Controllers\Owner\HeartbeatsController::class, 'index'])->name('heartbeats.index');
    });
});

// ── Public license heartbeat receiver (CSRF excluded in bootstrap/app.php) ─
// Buyer installs POST here. No auth — receiver only accepts known
// license_ids and worst-case spoofing just inflates noise on a license
// the spoofer already knows about.
Route::post('/api/license/heartbeat', [\App\Http\Controllers\Api\HeartbeatReceiver::class, 'store'])
    ->name('api.license.heartbeat');

// Inbound WhatsApp (Gupshup BSP) — patients message their vitals to the clinic
// number; this parses them into the diaries and replies a confirmation. Public
// (no auth/CSRF); guarded by ?token= matching services.whatsapp.gupshup.inbound_token.
// Token may be passed as a path segment (Gupshup's callback field rejects query
// strings) or as ?token=. GET returns 200 so a provider's save-time URL check passes.
Route::match(['get', 'post'], '/webhooks/whatsapp/inbound/{token?}', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle'])
    ->name('webhooks.whatsapp.inbound');
