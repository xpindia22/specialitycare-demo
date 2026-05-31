<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index()
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $files = collect(glob($dir . '/db-*.sql.gz') ?: [])
            ->map(fn ($f) => (object) [
                'name'  => basename($f),
                'size'  => filesize($f),
                'mtime' => \Carbon\Carbon::createFromTimestamp(filemtime($f)),
                'path'  => $f,
            ])
            ->sortByDesc('mtime')
            ->values();
        return view('admin.backup', compact('files'));
    }

    public function runBackup()
    {
        Artisan::call('specialitycare:backup');
        $output = trim(Artisan::output());
        return back()->with('success', $output ?: 'Backup completed.');
    }

    public function download(Request $request, string $name)
    {
        // Sanity: only allow files matching the dump pattern, no path traversal
        if (!preg_match('/^db-[\w\-\.]+\.sql\.gz$/', $name)) abort(404);
        $path = storage_path('app/backups/' . $name);
        if (!file_exists($path)) abort(404);
        return response()->download($path, $name, ['Content-Type' => 'application/gzip']);
    }

    /**
     * Stream a CSV export of one of the supported tables.
     * Whitelisted to avoid arbitrary table dumps.
     */
    public function exportCsv(string $table)
    {
        $whitelist = [
            'patients'              => ['id','jhc_id','first_name','last_name','date_of_birth','gender','phone','email','blood_group','address','registered_at','created_at'],
            'encounters'            => ['id','encounter_no','patient_id','doctor_id','encounter_date','visit_type','status','chief_complaint','impression','plan','created_at'],
            'bills'                 => ['id','bill_no','patient_id','encounter_id','billing_package_id','bill_date','subtotal','discount_amount','total_amount','paid_amount','due_amount','status','payment_method'],
            'lab_orders'            => ['id','order_no','patient_id','doctor_id','encounter_id','admission_id','ordered_date','status','total_amount','net_amount'],
            'prescriptions'         => ['id','prescription_no','patient_id','doctor_id','encounter_id','prescribed_date','status'],
            'admissions'            => ['id','ipd_no','patient_id','admission_date','discharge_date','status','discharge_condition','provisional_diagnosis'],
            'appointments'          => ['id','patient_id','doctor_id','appointment_date','appointment_time','status','reason'],
        ];
        if (!isset($whitelist[$table])) abort(404);
        $cols = $whitelist[$table];

        $filename = "{$table}-" . now()->format('Ymd-His') . '.csv';

        return new StreamedResponse(function () use ($table, $cols) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $cols);
            DB::table($table)->orderBy('id')->select($cols)->chunkById(1000, function ($rows) use ($out, $cols) {
                foreach ($rows as $r) {
                    $line = [];
                    foreach ($cols as $c) $line[] = $r->{$c} ?? '';
                    fputcsv($out, $line);
                }
            });
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
