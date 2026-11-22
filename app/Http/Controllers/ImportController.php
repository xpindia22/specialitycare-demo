<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use PDO;

class ImportController extends Controller
{
    private string $scriptPath;
    private string $phpBin = '/usr/bin/php8.4';

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/import_openemr.php');
    }

    // ── Index ─────────────────────────────────────────────────────────────
    public function index()
    {
        $scriptExists  = file_exists($this->scriptPath);
        $scriptModified = $scriptExists ? date('Y-m-d H:i:s', filemtime($this->scriptPath)) : null;
        return view('import.index', compact('scriptExists', 'scriptModified'));
    }

    // ── Upload the PHP import script to scripts/ ──────────────────────────
    public function uploadScript(Request $request)
    {
        $request->validate(['script_file' => 'required|file']);
        $file = $request->file('script_file');
        if (strtolower($file->getClientOriginalExtension()) !== 'php') {
            return back()->with('error', 'Only PHP files can be uploaded as the import script.');
        }
        $file->move(base_path('scripts'), 'import_openemr.php');
        return back()->with('success', 'Script uploaded successfully to scripts/import_openemr.php');
    }

    // ── Run the import script (live DB or SQL file) ───────────────────────
    public function runImport(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        // Ensure JSON is always returned even on uncaught exceptions
        try { return $this->doRunImport($request); }
        catch (\Throwable $e) {
            return response()->json(['output' => '[ERR]   ' . $e->getMessage()]);
        }
    }

    private function doRunImport(Request $request)
    {
        @set_time_limit(0);

        $request->validate([
            'source_type'  => 'required|in:livedb,sqlfile,serverpath',
            'openemr_db'   => 'nullable|string|max:64',
            'openemr_host' => 'nullable|string|max:128',
            'openemr_user' => 'nullable|string|max:64',
            'openemr_pass' => 'nullable|string|max:128',
            'server_path'  => 'nullable|string|max:512',
            'sql_file'     => 'nullable|file',
            'only_module'  => 'nullable|in:patients,encounters,prescriptions,billing,clinical_notes,logs',
            'dry_run'      => 'nullable',
            'all_logs'     => 'nullable',
        ]);

        if (!file_exists($this->scriptPath)) {
            return response()->json(['output' => '[ERR]   Import script not found at scripts/import_openemr.php — please upload it first.']);
        }

        $sourceType = $request->source_type;
        $dryRun     = $request->filled('dry_run');
        $onlyModule = $request->only_module ?: null;

        if ($sourceType === 'livedb') {
            $output = $this->runAgainstDb(
                $request->openemr_db   ?: 'open72',
                $request->openemr_host ?: '127.0.0.1',
                $request->openemr_user ?: config('database.connections.mysql.username', 'root'),
                $request->openemr_pass ?: config('database.connections.mysql.password', ''),
                $dryRun,
                $onlyModule
            );
            return response()->json(['output' => $output]);
        }

        // Resolve SQL file path
        $isGzip = false;
        if ($sourceType === 'sqlfile') {
            if (!$request->hasFile('sql_file')) {
                return response()->json(['output' => '[ERR]   No SQL file uploaded.']);
            }
            $uploaded = $request->file('sql_file');
            // PHP temp files have no extension — detect gzip from original filename or magic bytes
            $origName = strtolower($uploaded->getClientOriginalName());
            $isGzip   = str_ends_with($origName, '.gz');
            $sqlPath  = $uploaded->getPathname();
        } else {
            $sqlPath = trim($request->server_path ?? '');
            if (!$sqlPath || !file_exists($sqlPath)) {
                return response()->json(['output' => "[ERR]   File not found on server: {$sqlPath}"]);
            }
            $isGzip = str_ends_with(strtolower($sqlPath), '.gz');
        }

        // Import SQL into a temp DB, run script, then drop temp DB
        $output = $this->runFromSqlFile($sqlPath, $isGzip, $dryRun, $onlyModule);
        return response()->json(['output' => $output]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** SC database credentials as env vars for the subprocess. */
    private function scEnv(): array
    {
        return [
            'DB_HOST'     => config('database.connections.mysql.host',     '127.0.0.1'),
            'DB_PORT'     => config('database.connections.mysql.port',     '3306'),
            'DB_DATABASE' => config('database.connections.mysql.database', 'sc'),
            'DB_USERNAME' => config('database.connections.mysql.username', 'root'),
            'DB_PASSWORD' => config('database.connections.mysql.password', ''),
        ];
    }

    private function runAgainstDb(
        string $emrDb, string $emrHost, string $emrUser, string $emrPass,
        bool $dryRun, ?string $only
    ): string {
        $args = [
            $this->phpBin,
            $this->scriptPath,
            '--openemr-db='   . $emrDb,
            '--openemr-host=' . $emrHost,
            '--openemr-user=' . $emrUser,
            '--openemr-pass=' . $emrPass,
        ];
        if ($dryRun)  $args[] = '--dry-run';
        if ($only)    $args[] = '--only=' . $only;
        if (request()->filled('all_logs')) $args[] = '--all-logs';

        return $this->execProcess($args, base_path(), $this->scEnv());
    }

    private function runFromSqlFile(string $sqlPath, bool $isGzip, bool $dryRun, ?string $only): string
    {
        $host  = config('database.connections.mysql.host',     '127.0.0.1');
        $port  = config('database.connections.mysql.port',     '3306');
        $user  = config('database.connections.mysql.username', 'root');
        $pass  = config('database.connections.mysql.password', '');

        $tmpDb = 'oemr_tmp_' . substr(md5(uniqid('', true)), 0, 10);
        $log   = [];

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $user, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("CREATE DATABASE `{$tmpDb}` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $log[] = "[INFO]  Created temp database: {$tmpDb}";

            // Stream SQL file into mysql CLI (supports plain .sql and .sql.gz / .gz)
            // Strip CREATE DATABASE / USE statements so data lands in $tmpDb, not the original DB.
            $mysqlBin = trim(shell_exec('which mysql 2>/dev/null') ?: '/usr/bin/mysql');
            $sedBin   = trim(shell_exec('which sed 2>/dev/null') ?: '/usr/bin/sed');
            $stripCmd = escapeshellcmd($sedBin) . " '/^\\s*CREATE DATABASE/Id;/^\\s*USE /Id'";
            $mysqlPart = sprintf(
                '%s -h%s -P%s -u%s -p%s --force %s',
                escapeshellcmd($mysqlBin),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($tmpDb)
            );
            if ($isGzip) {
                $zcatBin = trim(shell_exec('which zcat 2>/dev/null') ?: '/bin/zcat');
                $cmd = escapeshellcmd($zcatBin) . ' ' . escapeshellarg($sqlPath)
                     . ' | ' . $stripCmd . ' | ' . $mysqlPart . ' 2>&1';
            } else {
                $cmd = 'cat ' . escapeshellarg($sqlPath)
                     . ' | ' . $stripCmd . ' | ' . $mysqlPart . ' 2>&1';
            }
            $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = proc_open($cmd, $descriptors, $pipes);
            $mysqlOut = '';
            if (is_resource($proc)) {
                $mysqlOut = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
                fclose($pipes[1]); fclose($pipes[2]);
                $ret = proc_close($proc);
            } else {
                $ret = 1;
            }

            if ($ret !== 0) {
                throw new \RuntimeException("SQL import failed (exit {$ret}):\n" . $mysqlOut);
            }
            $log[] = "[INFO]  SQL file imported into {$tmpDb}" . ($mysqlOut ? "\n" . $mysqlOut : '');

            // Run import script against temp DB
            $scriptOut = $this->runAgainstDb($tmpDb, $host, $user, $pass, $dryRun, $only);
            $log[] = $scriptOut;

        } catch (\Throwable $e) {
            $log[] = "[ERR]   " . $e->getMessage();
        } finally {
            try {
                if (isset($pdo)) {
                    $pdo->exec("DROP DATABASE IF EXISTS `{$tmpDb}`");
                    $log[] = "[INFO]  Dropped temp database: {$tmpDb}";
                }
            } catch (\Throwable $ignored) {}
        }

        return implode("\n", $log);
    }

    private function execProcess(array $cmd, string $cwd, array $env = []): string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptors, $pipes, $cwd, $env ?: null);
        if (!is_resource($process)) return '[ERR]   Failed to start import process.';
        fclose($pipes[0]);
        $output  = stream_get_contents($pipes[1]);
        $output .= stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        proc_close($process);
        return $output ?: '(no output)';
    }

    // ── Legacy CSV import (kept) ──────────────────────────────────────────
    public function importOpenEMR(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
        $handle   = fopen($request->file('csv_file')->getPathname(), 'r');
        $headers  = fgetcsv($handle);
        $imported = 0; $skipped = 0; $errors = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($headers)) { $skipped++; continue; }
            $data = array_combine($headers, $row);
            $pid  = $data['pid'] ?? null;
            if ($pid && Patient::where('openemr_pid', $pid)->exists()) { $skipped++; continue; }
            try {
                Patient::create([
                    'jhc_id'        => Patient::generateJhcId(),
                    'openemr_pid'   => $pid,
                    'first_name'    => $data['fname']       ?? '',
                    'last_name'     => $data['lname']       ?? '',
                    'gender'        => ucfirst(strtolower($data['sex'] ?? 'Unknown')),
                    'date_of_birth' => $data['DOB']         ?? now()->toDateString(),
                    'phone'         => $data['phone_cell']  ?? $data['phone_home'] ?? '0000000000',
                    'address'       => $data['street']      ?? '',
                    'city'          => $data['city']        ?? '',
                    'state'         => $data['state']       ?? '',
                    'is_active'     => true,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = $e->getMessage();
            }
        }
        fclose($handle);
        return back()->with([
            'success'       => "CSV import done — Imported: {$imported}, Skipped/Errors: {$skipped}",
            'import_errors' => array_slice($errors, 0, 10),
        ]);
    }
}
