<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Export / migrate SpecialityCare -> OpenEMR (reverse of ImportController).
 * Runs scripts/export_to_openemr.php against a target OpenEMR database.
 * Admin-gated (admin.import). LOCAL / dev migration tool.
 */
class ExportController extends Controller
{
    private string $scriptPath;
    private string $phpBin = '/usr/bin/php8.4';

    /** Stages the export script understands, in dependency order. */
    public const STAGES = ['providers','patients','encounters','pharmacy','prescriptions','lab','billing','clinical_notes','allergies','problems','medications','vitals','appointments','notes'];

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/export_to_openemr.php');
    }

    public function index()
    {
        $scriptExists   = file_exists($this->scriptPath);
        $scriptModified = $scriptExists ? date('Y-m-d H:i:s', filemtime($this->scriptPath)) : null;
        return view('export.index', [
            'scriptExists'   => $scriptExists,
            'scriptModified' => $scriptModified,
            'stages'         => self::STAGES,
        ]);
    }

    public function runExport(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        try { return $this->doRun($request); }
        catch (\Throwable $e) {
            return response()->json(['output' => "\033[31m[ERR]\033[0m   " . $e->getMessage()]);
        }
    }

    private function doRun(Request $request)
    {
        $request->validate([
            'target_db'  => 'required|string|max:64',
            'clone_from' => 'nullable|string|max:64',
            'rebuild'    => 'nullable',
            'dry_run'    => 'nullable',
            'only'       => 'nullable|in:' . implode(',', self::STAGES),
        ]);

        if (!file_exists($this->scriptPath)) {
            return response()->json(['output' => "\033[31m[ERR]\033[0m   Export script not found at scripts/export_to_openemr.php"]);
        }

        $target  = strtolower(trim($request->target_db));
        $cloneFrom = strtolower(trim($request->clone_from ?: 'open72'));
        $rebuild = $request->filled('rebuild');
        $dryRun  = $request->filled('dry_run');
        $only    = $request->only ?: null;

        // ── SAFETY GUARDS ──────────────────────────────────────────────────
        // --reset DROPs the target DB, so the target must be a disposable *_sc
        // database, never a live DB (the SC app DB, the source OpenEMR, etc.).
        if (!preg_match('/^[a-z0-9_]+_sc$/', $target)) {
            return response()->json(['output' =>
                "\033[31m[ERR]\033[0m   Refusing to use target '{$target}'. The target OpenEMR database name must end in '_sc' "
                . "(e.g. open72_sc) — this is a disposable copy that gets wiped/rebuilt. It must never be a live database."]);
        }
        $forbidden = [
            strtolower(config('database.connections.mysql.database', 'sc')),
            'open72','open72_live','mysql','information_schema','performance_schema','sys',
        ];
        if (in_array($target, $forbidden, true)) {
            return response()->json(['output' => "\033[31m[ERR]\033[0m   Target '{$target}' is a protected database and cannot be overwritten."]);
        }
        if ($rebuild && $target === $cloneFrom) {
            return response()->json(['output' => "\033[31m[ERR]\033[0m   Target and 'clone from' source cannot be the same database when rebuilding."]);
        }

        $host = config('database.connections.mysql.host',     '127.0.0.1');
        $port = config('database.connections.mysql.port',     '3306');
        $user = config('database.connections.mysql.username', 'root');
        $pass = config('database.connections.mysql.password', '');
        $scDb = config('database.connections.mysql.database', 'specialitycare');

        $args = [
            $this->phpBin,
            $this->scriptPath,
            '--host='   . $host,
            '--user='   . $user,
            '--pass='   . $pass,
            '--sc-db='  . $scDb,
            '--emr-db=' . $target,
        ];
        if ($rebuild) { $args[] = '--reset'; $args[] = '--clone-from=' . $cloneFrom; }
        if ($dryRun)  { $args[] = '--dry-run'; }
        if ($only)    { $args[] = '--only=' . $only; }

        $output = $this->execProcess($args, base_path(), [
            'DB_HOST' => $host, 'DB_PORT' => $port, 'DB_DATABASE' => $scDb,
            'DB_USERNAME' => $user, 'DB_PASSWORD' => $pass, 'MYSQL_PWD' => $pass,
            'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
        ]);

        return response()->json(['output' => $output]);
    }

    private function execProcess(array $cmd, string $cwd, array $env = []): string
    {
        $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
        $process = proc_open($cmd, $descriptors, $pipes, $cwd, $env ?: null);
        if (!is_resource($process)) return "\033[31m[ERR]\033[0m   Failed to start export process.";
        fclose($pipes[0]);
        $out  = stream_get_contents($pipes[1]);
        $out .= stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        proc_close($process);
        return $out ?: '(no output)';
    }
}
