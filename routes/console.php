<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Run daily room-rent charges at midnight
Schedule::command('ipd:room-charges')->dailyAt('00:05');

// Multi-channel overdue bill reminders (email + WhatsApp + owner CC), gated
// by notification_preferences[bill_overdue]. OpenEMR-imported (OE/...) bills
// are excluded by default since their paid/due values are unreliable.
// Re-enabled 2026-05-25 with: OE exclusion, last_overdue_reminded_at dedup
// (7-day repeat window), and the unified PatientNotifier path.
Schedule::command('bills:send-overdue-reminders')->dailyAt('09:00');

// WhatsApp: day-before appointment reminders, evening of day before.
Schedule::command('appointments:send-whatsapp-reminders')->dailyAt('18:00');

// Email: day-before appointment reminders (to patient, CC clinic).
Schedule::command('appointments:send-email-reminders')->dailyAt('18:05');

// Keep encounter-reports symlinked to the external store (auto-heal after
// restores so the backed-up project folder stays small). No-ops on machines
// without the external store (e.g. nvme).
Schedule::command('reports:ensure-external')->hourly();

// WhatsApp: encounter follow-up reminders, evening of day before.
Schedule::command('encounters:send-followup-whatsapp')->dailyAt('18:15');

// WhatsApp: vaccinations recommended within next 3 days.
// DISABLED 2026-06-12: clinic is not offering immunizations at present, so we
// don't send vaccination-due reminders. Re-enable this line (and redeploy) when
// immunization services resume. The command itself still exists for manual runs.
// Schedule::command('vaccinations:send-due-whatsapp')->dailyAt('09:30');

// (bills:send-overdue-whatsapp was retired 2026-05-25 — folded into the
// unified bills:send-overdue-reminders command above. The legacy artisan
// signature is still aliased for any external scripts referencing it.)

// Patient-growth + clinical reminders (added 2026-05-23).
Schedule::command('reviews:send-request-whatsapp')->dailyAt('10:00');               // 2 days after visit
Schedule::command('patients:send-birthday-whatsapp')->dailyAt('09:00');             // happy birthday
Schedule::command('patients:send-medication-reminder-whatsapp')->dailyAt('08:00');  // chronic patients
Schedule::command('prescriptions:send-refill-reminder-whatsapp')->dailyAt('10:30'); // refill reminder, 3 days before meds run out

// PULL-ONLY sync. Nvme is production / source of truth; home is the dev
// playground that mirrors it. We only ever PULL nvme → home, never push the
// other way (a home experiment must not overwrite live production data).
// The sync:to-nvme artisan command still exists for rare manual one-offs,
// but it is intentionally NOT scheduled.
// Gated by SYNC_ENABLED so buyers / nvme itself never run it.
if (env('SYNC_ENABLED', false)) {
    Schedule::command('sync:from-nvme')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
}

// Telemetry + revocation poll: phone home so the owner can detect license
// cloning AND so revoking a license actually disables the install within
// the hour. No-op when MODULES_HEARTBEAT_URL is unset (i.e. on the owner's
// central server). The HeartbeatService throttle (config modules.heartbeat_interval,
// default 3300s) keeps requests under one per scheduled run.
Schedule::command('license:heartbeat')->hourly();

// Accounts: auto-post Payment vouchers for active recurring expenses
// whose day_of_month matches today and which haven't been posted this month.
Schedule::command('accounts:post-recurring')->dailyAt('00:15');

// HR: auto-create Present attendance for any staff flagged `auto_present`
// on every missing day from their joining_date to today. Idempotent — never
// overwrites a manually marked record. Runs daily so today's record exists
// even if no one opens the attendance page.
Schedule::command('attendance:auto-mark-present')->dailyAt('00:20');

use Illuminate\Support\Facades\DB;
use App\Models\Bill;
use App\Models\Doctor;
use App\Models\Encounter;
use App\Models\EncounterReport;
use App\Models\LabCategory;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\Patient;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('import:openemr-bills {--source=open72} {--dry-run}', function () {
    $source = preg_replace('/[^A-Za-z0-9_]/', '', (string) $this->option('source'));
    $dryRun = (bool) $this->option('dry-run');
    $now = now();
    $createdBy = DB::table('users')->orderBy('id')->value('id');
    $normalizePaymentMethod = function (?string $method): string {
        $value = strtolower((string) $method);

        return match (true) {
            str_contains($value, 'card') => 'Card',
            str_contains($value, 'upi'),
            str_contains($value, 'gpay'),
            str_contains($value, 'google'),
            str_contains($value, 'phonepe'),
            str_contains($value, 'paytm') => 'UPI',
            default => 'Cash',
        };
    };

    if (!$createdBy) {
        $this->error('No SpecialityCare user found for created_by/received_by.');
        return self::FAILURE;
    }

    $groups = DB::table(DB::raw("{$source}.billing as b"))
        ->selectRaw('b.pid, b.encounter, MIN(DATE(b.date)) as bill_date, SUM(COALESCE(NULLIF(b.units, 0), 1) * COALESCE(b.fee, 0)) as subtotal, COUNT(*) as item_count')
        ->whereNull('b.deleted_at')
        ->groupBy('b.pid', 'b.encounter')
        ->havingRaw('subtotal > 0')
        ->orderBy('bill_date')
        ->get();

    $patientsCreated = 0;
    $billsImported = 0;
    $billsSkipped = 0;
    $itemsImported = 0;
    $paymentsImported = 0;
    $missingPatients = 0;

    if ($dryRun) {
        $this->info("Dry run: {$groups->count()} OpenEMR bill groups found.");
    }

    DB::beginTransaction();

    try {
        foreach ($groups as $group) {
            $billNo = 'OE/' . $group->pid . '/' . $group->encounter;

            if (Bill::where('bill_no', $billNo)->exists()) {
                $billsSkipped++;
                continue;
            }

            $patient = Patient::where('openemr_pid', (string) $group->pid)->first();
            if (!$patient) {
                $openemrPatient = DB::table(DB::raw("{$source}.patient_data"))
                    ->where('pid', $group->pid)
                    ->first();

                $patient = Patient::create([
                    'jhc_id' => Patient::generateJhcId(),
                    'openemr_pid' => (string) ($openemrPatient->pid ?? $group->pid),
                    'first_name' => ($openemrPatient->fname ?? null) ?: 'OpenEMR',
                    'middle_name' => ($openemrPatient->mname ?? null) ?: null,
                    'last_name' => ($openemrPatient->lname ?? null) ?: 'Patient ' . $group->pid,
                    'gender' => match (strtolower((string) ($openemrPatient->sex ?? ''))) {
                        'male', 'm' => 'Male',
                        'female', 'f' => 'Female',
                        'other' => 'Other',
                        default => 'Unknown',
                    },
                    'date_of_birth' => ($openemrPatient->DOB ?? null) ?: '1900-01-01',
                    'phone' => ($openemrPatient->phone_cell ?? null) ?: (($openemrPatient->phone_home ?? null) ?: '0000000000'),
                    'email' => ($openemrPatient->email ?? null) ?: null,
                    'address' => ($openemrPatient->street ?? null) ?: '',
                    'city' => ($openemrPatient->city ?? null) ?: null,
                    'state' => ($openemrPatient->state ?? null) ?: null,
                    'pincode' => ($openemrPatient->postal_code ?? null) ?: null,
                    'country' => 'India',
                    'occupation' => ($openemrPatient->occupation ?? null) ?: null,
                    'religion' => ($openemrPatient->religion ?? null) ?: null,
                    'nationality' => 'Indian',
                    'is_active' => true,
                ]);
                $patientsCreated++;
                if (!$openemrPatient) {
                    $missingPatients++;
                }
            }

            $payments = DB::table(DB::raw("{$source}.payments"))
                ->selectRaw('SUM(COALESCE(amount1, 0) + COALESCE(amount2, 0)) as amount, MIN(DATE(dtime)) as payment_date, GROUP_CONCAT(DISTINCT NULLIF(method, "") ORDER BY method SEPARATOR ", ") as methods, GROUP_CONCAT(DISTINCT NULLIF(source, "") ORDER BY source SEPARATOR ", ") as refs')
                ->whereNull('deleted_at')
                ->where('pid', $group->pid)
                ->where('encounter', $group->encounter)
                ->first();

            $subtotal = round((float) $group->subtotal, 2);
            $originalPaid = round((float) ($payments->amount ?? 0), 2);
            $paid = min($subtotal, $originalPaid);
            $due = max(0, $subtotal - $paid);
            $paymentMethod = $normalizePaymentMethod($payments->methods ?? null);
            $notes = "Imported from OpenEMR pid {$group->pid}, encounter {$group->encounter}.";

            if ($originalPaid > $subtotal) {
                $notes .= " Original OpenEMR payment total: {$originalPaid}.";
            }

            $bill = Bill::create([
                'bill_no' => $billNo,
                'patient_id' => $patient->id,
                'encounter_id' => null,
                'bill_date' => $group->bill_date ?: $now->toDateString(),
                'subtotal' => $subtotal,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'status' => $due <= 0 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid'),
                'payment_method' => $paid > 0 ? $paymentMethod : null,
                'notes' => $notes,
                'created_by' => $createdBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $billsImported++;

            $items = DB::table(DB::raw("{$source}.billing"))
                ->whereNull('deleted_at')
                ->where('pid', $group->pid)
                ->where('encounter', $group->encounter)
                ->orderBy('id')
                ->get();

            foreach ($items as $item) {
                $quantity = max(1, (int) ($item->units ?: 1));
                $unitPrice = round((float) ($item->fee ?? 0), 2);

                $bill->items()->create([
                    'item_type' => $item->code_type ?: ($item->chargecat ?: 'OpenEMR'),
                    'item_name' => $item->code_text ?: ($item->code ?: ($item->external_id ?: 'OpenEMR charge')),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => round($quantity * $unitPrice, 2),
                    'reference_id' => $item->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $itemsImported++;
            }

            if ($paid > 0) {
                $bill->payments()->create([
                    'amount' => $paid,
                    'payment_method' => $paymentMethod,
                    'transaction_ref' => $payments->refs ?: null,
                    'payment_date' => $payments->payment_date ?: $group->bill_date ?: $now->toDateString(),
                    'notes' => 'Imported from OpenEMR payments table.',
                    'received_by' => $createdBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $paymentsImported++;
            }
        }

        if ($dryRun) {
            DB::rollBack();
        } else {
            DB::commit();
        }
    } catch (Throwable $e) {
        DB::rollBack();
        throw $e;
    }

    $prefix = $dryRun ? 'Dry run complete.' : 'Import complete.';
    $this->info("{$prefix} Patients created: {$patientsCreated}; bills imported: {$billsImported}; skipped existing: {$billsSkipped}; items imported: {$itemsImported}; payments imported: {$paymentsImported}; missing patients: {$missingPatients}.");

    return self::SUCCESS;
})->purpose('Import OpenEMR billing rows into SpecialityCare bills');

Artisan::command('import:openemr-labs {--source=open72} {--dry-run}', function () {
    $source = preg_replace('/[^A-Za-z0-9_]/', '', (string) $this->option('source'));
    $dryRun = (bool) $this->option('dry-run');
    $now = now();
    $createdBy = DB::table('users')->orderBy('id')->value('id');
    $doctorId = Doctor::where('is_active', true)->orderBy('id')->value('id') ?: Doctor::orderBy('id')->value('id');
    $patientsCreated = 0;
    $missingPatients = 0;
    $ordersImported = 0;
    $ordersSkipped = 0;
    $itemsImported = 0;
    $resultsImported = 0;
    $testsCreated = 0;

    if (!$createdBy) {
        $this->error('No SpecialityCare user found for created_by/reported_by.');
        return self::FAILURE;
    }

    if (!$doctorId) {
        $this->error('No SpecialityCare doctor found for imported lab orders.');
        return self::FAILURE;
    }

    $mapStatus = function (?string $status, bool $hasResults, bool $hasReport): string {
        $value = strtolower(trim((string) $status));

        return match (true) {
            str_contains($value, 'cancel') => 'Cancelled',
            str_contains($value, 'complete'), str_contains($value, 'final'), $hasResults => 'Completed',
            str_contains($value, 'collect'), $hasReport => 'Collected',
            str_contains($value, 'process'), str_contains($value, 'progress') => 'Processing',
            default => 'Ordered',
        };
    };

    $mapFlag = function (?string $abnormal): string {
        $value = strtolower(trim((string) $abnormal));

        return match (true) {
            str_contains($value, 'critical high') => 'Critical High',
            str_contains($value, 'critical low') => 'Critical Low',
            $value === 'high' || $value === 'h' => 'High',
            $value === 'low' || $value === 'l' => 'Low',
            in_array($value, ['yes', 'abnormal', 'a'], true) => 'High',
            default => 'Normal',
        };
    };

    $validDateTime = function ($value) use ($now) {
        if (!$value) {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }

        return $date->year >= 1900 ? $date : null;
    };

    $findOrCreatePatient = function ($openemrPid) use ($source, &$patientsCreated, &$missingPatients) {
        $patient = Patient::where('openemr_pid', (string) $openemrPid)->first();
        if ($patient) {
            return $patient;
        }

        $openemrPatient = DB::table(DB::raw("{$source}.patient_data"))
            ->where('pid', $openemrPid)
            ->first();

        if (!$openemrPatient) {
            $missingPatients++;
        }

        $patientsCreated++;

        return Patient::create([
            'jhc_id' => Patient::generateJhcId(),
            'openemr_pid' => (string) ($openemrPatient->pid ?? $openemrPid),
            'first_name' => ($openemrPatient->fname ?? null) ?: 'OpenEMR',
            'middle_name' => ($openemrPatient->mname ?? null) ?: null,
            'last_name' => ($openemrPatient->lname ?? null) ?: 'Patient ' . $openemrPid,
            'gender' => match (strtolower((string) ($openemrPatient->sex ?? ''))) {
                'male', 'm' => 'Male',
                'female', 'f' => 'Female',
                'other' => 'Other',
                default => 'Unknown',
            },
            'date_of_birth' => ($openemrPatient->DOB ?? null) ?: '1900-01-01',
            'phone' => ($openemrPatient->phone_cell ?? null) ?: (($openemrPatient->phone_home ?? null) ?: '0000000000'),
            'email' => ($openemrPatient->email ?? null) ?: null,
            'address' => ($openemrPatient->street ?? null) ?: '',
            'city' => ($openemrPatient->city ?? null) ?: null,
            'state' => ($openemrPatient->state ?? null) ?: null,
            'pincode' => ($openemrPatient->postal_code ?? null) ?: null,
            'country' => 'India',
            'occupation' => ($openemrPatient->occupation ?? null) ?: null,
            'religion' => ($openemrPatient->religion ?? null) ?: null,
            'nationality' => 'Indian',
            'is_active' => true,
        ]);
    };

    $orders = DB::table(DB::raw("{$source}.procedure_order as po"))
        ->select('po.*')
        ->whereNull('po.deleted_at')
        ->orderBy('po.procedure_order_id')
        ->get();

    if ($dryRun) {
        $this->info("Dry run: {$orders->count()} OpenEMR lab orders found.");
    }

    DB::beginTransaction();

    try {
        $category = LabCategory::firstOrCreate(
            ['code' => 'OPENEMR'],
            ['name' => 'OpenEMR Labs']
        );

        foreach ($orders as $openemrOrder) {
            $orderNo = 'OE-LAB/' . $openemrOrder->procedure_order_id;

            if (LabOrder::where('order_no', $orderNo)->exists()) {
                $ordersSkipped++;
                continue;
            }

            $reportCount = DB::table(DB::raw("{$source}.procedure_report"))
                ->where('procedure_order_id', $openemrOrder->procedure_order_id)
                ->whereNull('deleted_at')
                ->count();
            $resultCount = DB::table(DB::raw("{$source}.procedure_report as pr"))
                ->join(DB::raw("{$source}.procedure_result as res"), function ($join) {
                    $join->on('res.procedure_report_id', '=', 'pr.procedure_report_id')
                        ->whereNull('res.deleted_at');
                })
                ->where('pr.procedure_order_id', $openemrOrder->procedure_order_id)
                ->whereNull('pr.deleted_at')
                ->count();
            $patient = $findOrCreatePatient($openemrOrder->patient_id);
            $status = $mapStatus(
                $openemrOrder->order_status,
                $resultCount > 0,
                $reportCount > 0
            );
            $orderedDate = $validDateTime($openemrOrder->date_ordered)
                ?: ($validDateTime($openemrOrder->date_collected) ?: $now);
            $notes = "Imported from OpenEMR procedure_order {$openemrOrder->procedure_order_id}";
            if ($openemrOrder->encounter_id) {
                $notes .= ", encounter {$openemrOrder->encounter_id}";
            }
            $notes .= '.';

            $order = LabOrder::create([
                'order_no' => $orderNo,
                'patient_id' => $patient->id,
                'encounter_id' => null,
                'doctor_id' => $doctorId,
                'ordered_date' => $orderedDate,
                'status' => $status,
                'clinical_info' => $openemrOrder->clinical_hx ?: ($openemrOrder->order_diagnosis ?: null),
                'notes' => $notes,
                'total_amount' => 0,
                'discount' => 0,
                'net_amount' => 0,
                'created_by' => $createdBy,
                'created_at' => $validDateTime($openemrOrder->created_at) ?: $now,
                'updated_at' => $validDateTime($openemrOrder->modified_at) ?: $now,
            ]);
            $ordersImported++;

            $codes = DB::table(DB::raw("{$source}.procedure_order_code"))
                ->where('procedure_order_id', $openemrOrder->procedure_order_id)
                ->whereNull('deleted_at')
                ->orderBy('procedure_order_seq')
                ->get();

            foreach ($codes as $code) {
                $testName = trim((string) ($code->procedure_name ?: ($code->procedure_code ?: $code->procedure_order_title)));
                if ($testName === '') {
                    $testName = 'OpenEMR lab test ' . $code->procedure_order_seq;
                }

                $test = LabTest::where('lab_category_id', $category->id)
                    ->where('name', $testName)
                    ->first();

                if (!$test) {
                    $test = LabTest::create([
                        'lab_category_id' => $category->id,
                        'name' => $testName,
                        'short_code' => $code->procedure_code ?: null,
                        'description' => $code->procedure_order_title ?: $code->procedure_type ?: null,
                        'sample_type' => $openemrOrder->specimen_type ?: null,
                        'price' => 0,
                        'is_active' => true,
                    ]);
                    $testsCreated++;
                }

                $reports = DB::table(DB::raw("{$source}.procedure_report"))
                    ->where('procedure_order_id', $code->procedure_order_id)
                    ->where('procedure_order_seq', $code->procedure_order_seq)
                    ->whereNull('deleted_at')
                    ->orderBy('procedure_report_id')
                    ->get();

                $itemStatus = $mapStatus(
                    $openemrOrder->order_status,
                    $reports->isNotEmpty(),
                    $reports->isNotEmpty()
                );

                $item = $order->items()->create([
                    'lab_test_id' => $test->id,
                    'lab_panel_id' => null,
                    'price' => 0,
                    'status' => $itemStatus,
                    'created_at' => $validDateTime($code->created_at) ?: $now,
                    'updated_at' => $validDateTime($code->modified_at) ?: $now,
                ]);
                $itemsImported++;

                foreach ($reports as $report) {
                    $results = DB::table(DB::raw("{$source}.procedure_result"))
                        ->where('procedure_report_id', $report->procedure_report_id)
                        ->whereNull('deleted_at')
                        ->orderBy('procedure_result_id')
                        ->get();

                    foreach ($results as $result) {
                        $parameterName = trim((string) ($result->result_text ?: ($result->result_code ?: $test->name)));
                        $item->results()->create([
                            'lab_test_id' => $test->id,
                            'parameter_name' => $parameterName !== '' ? $parameterName : $test->name,
                            'result_value' => (string) $result->result,
                            'unit' => $result->units ?: null,
                            'normal_range' => $result->range ?: null,
                            'flag' => $mapFlag($result->abnormal),
                            'comments' => $result->comments ?: $report->report_notes ?: null,
                            'reported_by' => $createdBy,
                            'reported_at' => $validDateTime($result->date) ?: $validDateTime($report->date_report),
                            'created_at' => $validDateTime($result->created_at) ?: $now,
                            'updated_at' => $validDateTime($result->modified_at) ?: $now,
                        ]);
                        $resultsImported++;
                    }
                }
            }
        }

        if ($dryRun) {
            DB::rollBack();
        } else {
            DB::commit();
        }
    } catch (Throwable $e) {
        DB::rollBack();
        throw $e;
    }

    $prefix = $dryRun ? 'Dry run complete.' : 'Import complete.';
    $this->info("{$prefix} Patients created: {$patientsCreated}; lab tests created: {$testsCreated}; lab orders imported: {$ordersImported}; skipped existing: {$ordersSkipped}; items imported: {$itemsImported}; results imported: {$resultsImported}; missing patients: {$missingPatients}.");

    return self::SUCCESS;
})->purpose('Import OpenEMR procedure lab orders and results into SpecialityCare lab reports');

Artisan::command('import:openemr-reports {--source=open72} {--documents-root=/var/www/html/open72/sites/default/documents} {--dry-run}', function () {
    $source = preg_replace('/[^A-Za-z0-9_]/', '', (string) $this->option('source'));
    $documentsRoot = rtrim((string) $this->option('documents-root'), '/');
    $dryRun = (bool) $this->option('dry-run');
    $now = now();
    $createdBy = DB::table('users')->orderBy('id')->value('id');
    $doctorId = Doctor::where('is_active', true)->orderBy('id')->value('id') ?: Doctor::orderBy('id')->value('id');

    if (!$createdBy) {
        $this->error('No SpecialityCare user found for created_by/uploaded_by.');
        return self::FAILURE;
    }

    if (!$doctorId) {
        $this->error('No SpecialityCare doctor found for imported report visits.');
        return self::FAILURE;
    }

    $validDateTime = function ($value) use ($now) {
        if (!$value) {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }

        return $date->year >= 1900 ? $date : null;
    };

    $findOrCreatePatient = function ($openemrPid) use ($source, &$patientsCreated, &$missingPatients) {
        $patient = Patient::where('openemr_pid', (string) $openemrPid)->first();
        if ($patient) {
            return $patient;
        }

        $openemrPatient = DB::table(DB::raw("{$source}.patient_data"))
            ->where('pid', $openemrPid)
            ->first();

        if (!$openemrPatient) {
            $missingPatients++;
        }

        $patientsCreated++;

        return Patient::create([
            'jhc_id' => Patient::generateJhcId(),
            'openemr_pid' => (string) ($openemrPatient->pid ?? $openemrPid),
            'first_name' => ($openemrPatient->fname ?? null) ?: 'OpenEMR',
            'middle_name' => ($openemrPatient->mname ?? null) ?: null,
            'last_name' => ($openemrPatient->lname ?? null) ?: 'Patient ' . $openemrPid,
            'gender' => match (strtolower((string) ($openemrPatient->sex ?? ''))) {
                'male', 'm' => 'Male',
                'female', 'f' => 'Female',
                'other' => 'Other',
                default => 'Unknown',
            },
            'date_of_birth' => ($openemrPatient->DOB ?? null) ?: '1900-01-01',
            'phone' => ($openemrPatient->phone_cell ?? null) ?: (($openemrPatient->phone_home ?? null) ?: '0000000000'),
            'email' => ($openemrPatient->email ?? null) ?: null,
            'address' => ($openemrPatient->street ?? null) ?: '',
            'city' => ($openemrPatient->city ?? null) ?: null,
            'state' => ($openemrPatient->state ?? null) ?: null,
            'pincode' => ($openemrPatient->postal_code ?? null) ?: null,
            'country' => 'India',
            'occupation' => ($openemrPatient->occupation ?? null) ?: null,
            'religion' => ($openemrPatient->religion ?? null) ?: null,
            'nationality' => 'Indian',
            'is_active' => true,
        ]);
    };

    $resolveSourcePath = function (?string $url) use ($documentsRoot) {
        if (!$url) {
            return null;
        }

        $path = preg_replace('#^file://#', '', $url);
        if (is_readable($path)) {
            return $path;
        }

        $marker = '/sites/default/documents/';
        $pos = strpos($path, $marker);
        if ($pos !== false) {
            $relative = substr($path, $pos + strlen($marker));
            $candidate = $documentsRoot . '/' . ltrim($relative, '/');
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    };

    $reportTypeFrom = function (?string $category, ?string $name, ?string $mime): string {
        $value = strtolower(trim(($category ?? '') . ' ' . ($name ?? '') . ' ' . ($mime ?? '')));

        return match (true) {
            str_contains($value, 'lab') => 'Lab Report',
            str_contains($value, 'echo') => 'Imaging',
            str_contains($value, 'ecg') => 'ECG',
            str_contains($value, 'xray'), str_contains($value, 'scan'), str_contains($value, 'ultrasound') => 'Imaging',
            str_contains($value, 'opd') => 'OPD Slip',
            default => $category ?: 'Medical Record',
        };
    };

    $documents = DB::table(DB::raw("{$source}.documents as d"))
        ->select('d.*')
        ->selectSub(function ($query) use ($source) {
            $query->from(DB::raw("{$source}.categories_to_documents as cd"))
                ->join(DB::raw("{$source}.categories as c"), function ($join) {
                    $join->on('c.id', '=', 'cd.category_id')
                        ->whereNull('c.deleted_at');
                })
                ->whereColumn('cd.document_id', 'd.id')
                ->whereNull('cd.deleted_at')
                ->orderBy('cd.category_id')
                ->limit(1)
                ->select('c.name');
        }, 'category_name')
        ->where('d.deleted', 0)
        ->whereNull('d.deleted_at')
        ->orderBy('d.id')
        ->get();

    $patientsCreated = 0;
    $missingPatients = 0;
    $encountersCreated = 0;
    $reportsImported = 0;
    $reportsSkipped = 0;
    $missingFiles = 0;
    $missingPatientsForDocs = 0;

    if ($dryRun) {
        $this->info("Dry run: {$documents->count()} OpenEMR document reports found.");
    }

    DB::beginTransaction();

    try {
        foreach ($documents as $document) {
            if (EncounterReport::where('source_system', $source)->where('source_id', (string) $document->id)->exists()) {
                $reportsSkipped++;
                continue;
            }

            $sourcePath = $resolveSourcePath($document->url);
            if (!$sourcePath) {
                $missingFiles++;
                continue;
            }

            if (!$document->foreign_id) {
                $missingPatientsForDocs++;
                continue;
            }

            $patient = $findOrCreatePatient($document->foreign_id);
            if (!$patient) {
                $missingPatientsForDocs++;
                continue;
            }

            $openemrEncounter = null;
            if ((int) $document->encounter_id > 0) {
                $openemrEncounter = DB::table(DB::raw("{$source}.form_encounter"))
                    ->where('encounter', $document->encounter_id)
                    ->where('pid', $document->foreign_id)
                    ->whereNull('deleted_at')
                    ->first();
            }

            $encounterNo = (int) $document->encounter_id > 0
                ? 'EMR/' . $document->encounter_id
                : 'EMR-DOC/' . $document->id;

            $encounter = Encounter::where('encounter_no', $encounterNo)->first();
            if (!$encounter && (int) $document->encounter_id === 0) {
                $docDate = $validDateTime($document->docdate) ?: $validDateTime($document->date);
                $encounter = Encounter::where('patient_id', $patient->id)
                    ->whereDate('encounter_date', $docDate ? $docDate->toDateString() : $now->toDateString())
                    ->orderBy('id')
                    ->first();
            }

            if (!$encounter) {
                $encounterDate = $validDateTime($openemrEncounter->date ?? null)
                    ?: ($validDateTime($document->docdate) ?: ($validDateTime($document->date) ?: $now));
                $encounter = Encounter::create([
                    'encounter_no' => $encounterNo,
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctorId,
                    'appointment_id' => null,
                    'encounter_date' => $encounterDate->toDateString(),
                    'encounter_time' => $encounterDate->format('H:i:s'),
                    'visit_type' => 'Review',
                    'status' => 'Completed',
                    'chief_complaint' => ($openemrEncounter->reason ?? null) ?: 'Imported OpenEMR report visit',
                    'created_by' => $createdBy,
                    'created_at' => $validDateTime($openemrEncounter->created_at ?? null) ?: $now,
                    'updated_at' => $validDateTime($openemrEncounter->modified_at ?? null) ?: $now,
                ]);
                $encountersCreated++;
            }

            $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) ($document->name ?: basename($sourcePath)));
            $targetPath = "encounter-reports/{$encounter->id}/openemr-{$document->id}-{$safeName}";
            Storage::disk('public')->put($targetPath, file_get_contents($sourcePath));

            EncounterReport::create([
                'encounter_id' => $encounter->id,
                'patient_id' => $patient->id,
                'title' => $document->name ?: 'OpenEMR report ' . $document->id,
                'report_type' => $reportTypeFrom($document->category_name, $document->name, $document->mimetype),
                'file_path' => $targetPath,
                'original_name' => $document->name ?: basename($sourcePath),
                'mime_type' => $document->mimetype ?: mime_content_type($sourcePath),
                'file_size' => $document->size ?: filesize($sourcePath),
                'notes' => "Imported from OpenEMR document {$document->id}.",
                'uploaded_by' => $createdBy,
                'source_system' => $source,
                'source_id' => (string) $document->id,
                'source_url' => $document->url,
                'created_at' => $validDateTime($document->created_at) ?: ($validDateTime($document->date) ?: $now),
                'updated_at' => $validDateTime($document->modified_at) ?: $now,
            ]);
            $reportsImported++;
        }

        if ($dryRun) {
            DB::rollBack();
        } else {
            DB::commit();
        }
    } catch (Throwable $e) {
        DB::rollBack();
        throw $e;
    }

    $prefix = $dryRun ? 'Dry run complete.' : 'Import complete.';
    $this->info("{$prefix} Patients created: {$patientsCreated}; visits created: {$encountersCreated}; reports imported: {$reportsImported}; skipped existing: {$reportsSkipped}; missing source files: {$missingFiles}; missing patients: {$missingPatients}; docs without patient: {$missingPatientsForDocs}.");

    return self::SUCCESS;
})->purpose('Import OpenEMR patient document reports into SpecialityCare visit reports');
