<?php
/**
 * SpecialityCare — Standalone Setup & Import
 * Drop in /public/, browse to /setup.php, run setup, then DELETE this file.
 */
declare(strict_types=1);

// ── Password gate ────────────────────────────────────────────────────────────
// WARNING: this script can drop databases, execute shell commands, and rewrite
// .env. The password below is the ONLY barrier when the file is reachable from
// the internet. Use a strong password, restrict via .htaccess/IP allowlist, or
// delete the file when not actively running setup.
const SETUP_PASSWORD = 'Open722sc!';
const SETUP_THROTTLE_MAX = 5;          // failed attempts before lockout
const SETUP_THROTTLE_WINDOW = 300;     // seconds
const SETUP_LOCKOUT_SECONDS = 900;     // 15-minute lockout

session_name('scsetup');
session_start();

// Logout
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$isAuthed = !empty($_SESSION['scsetup_authed']) && $_SESSION['scsetup_authed'] === true;

// Login attempt
$loginError = '';
if (!$isAuthed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scsetup_password'])) {
    $now = time();
    $attempts = $_SESSION['scsetup_attempts'] ?? [];
    $attempts = array_values(array_filter($attempts, fn($t) => $t > $now - SETUP_THROTTLE_WINDOW));
    $lockedUntil = $_SESSION['scsetup_locked_until'] ?? 0;

    if ($lockedUntil > $now) {
        $loginError = 'Too many failed attempts. Try again in ' . ceil(($lockedUntil - $now) / 60) . ' minute(s).';
    } elseif (hash_equals(SETUP_PASSWORD, (string) $_POST['scsetup_password'])) {
        session_regenerate_id(true);
        $_SESSION['scsetup_authed'] = true;
        $_SESSION['scsetup_attempts'] = [];
        $_SESSION['scsetup_locked_until'] = 0;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } else {
        $attempts[] = $now;
        $_SESSION['scsetup_attempts'] = $attempts;
        if (count($attempts) >= SETUP_THROTTLE_MAX) {
            $_SESSION['scsetup_locked_until'] = $now + SETUP_LOCKOUT_SECONDS;
            $loginError = 'Too many failed attempts. Locked out for ' . (SETUP_LOCKOUT_SECONDS / 60) . ' minutes.';
        } else {
            $remaining = SETUP_THROTTLE_MAX - count($attempts);
            $loginError = "Invalid password. {$remaining} attempt(s) remaining.";
        }
    }
}

// Block everything if not authenticated
if (!$isAuthed) {
    // AJAX endpoints get a 401 JSON response, not the login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['action'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'output' => '[ERR]   Unauthorized — session expired. Reload the page and log in again.']);
        exit;
    }
    http_response_code(401);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>SpecialityCare — Setup (locked)</title>
        <style>
            *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
            body{font-family:system-ui,sans-serif;background:#0f1f3d;color:#e2e8f0;
                 min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
            .box{background:#162545;border-radius:12px;padding:32px 28px;max-width:380px;width:100%;
                 box-shadow:0 20px 60px rgba(0,0,0,.5)}
            h1{font-size:18px;margin-bottom:6px;color:#fff}
            .sub{font-size:12.5px;color:#8ba0c0;margin-bottom:22px}
            label{display:block;font-size:11.5px;font-weight:600;color:#8ba0c0;margin-bottom:6px;
                  text-transform:uppercase;letter-spacing:.5px}
            input[type=password]{width:100%;padding:11px 13px;border:1px solid #2a3a5e;background:#0f1f3d;
                                 border-radius:7px;color:#fff;font-size:14px;outline:none}
            input[type=password]:focus{border-color:#0ab4b4;box-shadow:0 0 0 3px rgba(10,180,180,.18)}
            button{width:100%;margin-top:14px;padding:11px;background:#0ab4b4;color:#fff;border:none;
                   border-radius:7px;font-size:13.5px;font-weight:700;cursor:pointer;transition:.15s}
            button:hover{background:#089898}
            .err{background:rgba(232,76,76,.15);border:1px solid rgba(232,76,76,.4);color:#ffb4b4;
                 padding:9px 12px;border-radius:7px;font-size:12.5px;margin-bottom:14px}
            .warn{background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.35);color:#f5c87a;
                  padding:10px 12px;border-radius:7px;font-size:11.5px;margin-top:18px;line-height:1.5}
        </style>
    </head>
    <body>
        <div class="box">
            <h1>SpecialityCare Setup</h1>
            <p class="sub">Restricted area — password required.</p>
            <?php if ($loginError !== ''): ?>
                <div class="err"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <label for="scsetup_password">Password</label>
                <input type="password" id="scsetup_password" name="scsetup_password" autofocus required>
                <button type="submit">Unlock</button>
            </form>
            <div class="warn">
                This file can drop the database and run shell commands.
                Delete it from <code>public/</code> when you are done.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// ── End password gate ────────────────────────────────────────────────────────

$APP_ROOT   = dirname(__DIR__);                          // /var/www/html/specialitycare
$SCRIPT     = $APP_ROOT . '/scripts/import_openemr.php';
$PHP_BIN    = PHP_BINARY ?: '/usr/bin/php8.4';

// ── AJAX handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['action'])) {
    @set_time_limit(0);
    @ini_set('memory_limit', '512M');
    header('Content-Type: application/json; charset=utf-8');

    $action = $_GET['action'];
    $out = '';

    try {
        switch ($action) {

            // ── Test DB connection ────────────────────────────────────────
            case 'test-db':
                $c = dbCreds();
                $pdo = makePdo($c['host'], $c['port'], $c['user'], $c['pass'], null);
                $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
                echo json_encode(['ok' => true, 'output' => "[INFO]  Connected OK — MariaDB/MySQL {$ver}"]);
                exit;

            // ── Create DB + import schema ─────────────────────────────────
            case 'setup-db':
                $c    = dbCreds();
                $db   = trim($_POST['db_name'] ?? 'sc');
                $pdo  = makePdo($c['host'], $c['port'], $c['user'], $c['pass'], null);
                $log  = [];

                // Drop and recreate if requested
                if (!empty($_POST['drop_first'])) {
                    $pdo->exec("DROP DATABASE IF EXISTS `{$db}`");
                    $log[] = "[INFO]  Dropped database `{$db}`.";
                }

                // Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $log[] = "[INFO]  Database `{$db}` ready.";

                // Import schema SQL if provided
                $sqlPath = resolveSchemaFile($log);
                if ($sqlPath) {
                    $isGz  = str_ends_with(strtolower($sqlPath), '.gz');
                    $mysql = findBin('mysql');
                    $mp    = "{$mysql} -h".escapeshellarg($c['host'])." -P".escapeshellarg($c['port'])
                           ." -u".escapeshellarg($c['user'])." -p".escapeshellarg($c['pass'])
                           ." ".escapeshellarg($db);
                    if ($isGz) {
                        $zcat = findBin('zcat');
                        $cmd  = "{$zcat} ".escapeshellarg($sqlPath)." | {$mp} 2>&1";
                        $desc = [1=>['pipe','w'], 2=>['pipe','w']];
                    } else {
                        $cmd  = "{$mp} 2>&1";
                        $desc = [0=>['file',$sqlPath,'r'], 1=>['pipe','w'], 2=>['pipe','w']];
                    }
                    $proc = proc_open($cmd, $desc, $pipes);
                    $res  = is_resource($proc)
                          ? (stream_get_contents($pipes[1]).stream_get_contents($pipes[2]))
                          : 'proc_open failed';
                    foreach ($pipes as $p) { @fclose($p); }
                    if (is_resource($proc)) proc_close($proc);
                    $log[] = $res
                           ? "[WARN]  mysql: " . trim($res)
                           : "[INFO]  Schema imported into `{$db}`.";
                }

                // Write / update .env
                $envFile = $APP_ROOT . '/.env';
                if (file_exists($envFile)) {
                    $env = file_get_contents($envFile);
                    $env = setEnvVal($env, 'DB_HOST',     $c['host']);
                    $env = setEnvVal($env, 'DB_PORT',     $c['port']);
                    $env = setEnvVal($env, 'DB_DATABASE', $db);
                    $env = setEnvVal($env, 'DB_USERNAME', $c['user']);
                    $env = setEnvVal($env, 'DB_PASSWORD', $c['pass']);
                    file_put_contents($envFile, $env);
                    $log[] = "[INFO]  .env updated with new DB credentials.";
                }

                // Run artisan migrate if artisan exists
                $artisan = $APP_ROOT . '/artisan';
                if (file_exists($artisan)) {
                    $env2 = [
                        'DB_HOST'=>$c['host'], 'DB_PORT'=>$c['port'],
                        'DB_DATABASE'=>$db, 'DB_USERNAME'=>$c['user'], 'DB_PASSWORD'=>$c['pass'],
                    ];
                    $migrateOut = runMigrateWithRetry($PHP_BIN, $artisan, $APP_ROOT, $env2,
                                                     $c['host'], $c['port'], $c['user'], $c['pass'], $db);
                    $log[] = "[INFO]  artisan migrate:\n" . $migrateOut;
                }

                echo json_encode(['ok'=>true, 'output'=>implode("\n", $log)]);
                exit;

            // ── Run OpenEMR import ────────────────────────────────────────
            case 'run-import':
                $c       = dbCreds();
                $db      = trim($_POST['db_name'] ?? 'sc');
                $srcType = $_POST['source_type'] ?? 'livedb';
                $dryRun  = !empty($_POST['dry_run']);
                $only    = trim($_POST['only_module'] ?? '');
                $allLogs = !empty($_POST['all_logs']);

                if (!file_exists($SCRIPT)) {
                    echo json_encode(['ok'=>false, 'output'=>"[ERR]   Script not found: {$SCRIPT}"]);
                    exit;
                }

                $scEnv = [
                    'DB_HOST'=>$c['host'], 'DB_PORT'=>$c['port'],
                    'DB_DATABASE'=>$db, 'DB_USERNAME'=>$c['user'], 'DB_PASSWORD'=>$c['pass'],
                ];

                if ($srcType === 'livedb') {
                    $emrDb   = trim($_POST['openemr_db']   ?? 'open72');
                    $emrHost = trim($_POST['openemr_host'] ?? '127.0.0.1');
                    $emrUser = trim($_POST['openemr_user'] ?? $c['user']);
                    $emrPass = trim($_POST['openemr_pass'] ?? $c['pass']);
                    $out = runImportScript($SCRIPT, $PHP_BIN, $emrDb, $emrHost, $emrUser, $emrPass,
                                          $dryRun, $only ?: null, $allLogs, $APP_ROOT, $scEnv);
                } else {
                    // SQL file import
                    $log  = [];
                    $isGz = false;
                    if ($srcType === 'sqlfile' && !empty($_FILES['sql_file']['tmp_name'])) {
                        $orig    = strtolower($_FILES['sql_file']['name']);
                        $isGz    = str_ends_with($orig, '.gz');
                        $sqlPath = $_FILES['sql_file']['tmp_name'];
                    } elseif ($srcType === 'serverpath') {
                        $sqlPath = trim($_POST['server_path'] ?? '');
                        $isGz    = str_ends_with(strtolower($sqlPath), '.gz');
                    } else {
                        echo json_encode(['ok'=>false, 'output'=>'[ERR]   No SQL file provided.']);
                        exit;
                    }
                    if (!file_exists($sqlPath)) {
                        echo json_encode(['ok'=>false, 'output'=>"[ERR]   File not found: {$sqlPath}"]);
                        exit;
                    }
                    $tmpDb = 'oemr_tmp_' . substr(md5(uniqid('',true)), 0, 10);
                    $pdo   = makePdo($c['host'], $c['port'], $c['user'], $c['pass'], null);
                    $pdo->exec("CREATE DATABASE `{$tmpDb}` DEFAULT CHARSET utf8mb4");
                    $log[] = "[INFO]  Created temp DB: {$tmpDb}";
                    try {
                        $mysql = findBin('mysql');
                        $sed   = findBin('sed');
                        // Strip CREATE DATABASE / USE lines so data lands in $tmpDb, not original DB
                        $strip = "{$sed} '/^\\s*CREATE DATABASE/Id;/^\\s*USE /Id'";
                        $mp    = "{$mysql} -h".escapeshellarg($c['host'])." -P".escapeshellarg($c['port'])
                               ." -u".escapeshellarg($c['user'])." -p".escapeshellarg($c['pass'])
                               ." --force ".escapeshellarg($tmpDb);
                        if ($isGz) {
                            $zcat = findBin('zcat');
                            $cmd  = "{$zcat} ".escapeshellarg($sqlPath)." | {$strip} | {$mp} 2>&1";
                        } else {
                            $cmd  = "cat ".escapeshellarg($sqlPath)." | {$strip} | {$mp} 2>&1";
                        }
                        $desc = [1=>['pipe','w'], 2=>['pipe','w']];
                        $proc = proc_open($cmd, $desc, $pipes);
                        $res  = is_resource($proc) ? (stream_get_contents($pipes[1]).stream_get_contents($pipes[2])) : 'proc_open failed';
                        foreach ($pipes as $p) { @fclose($p); }
                        if (is_resource($proc)) proc_close($proc);
                        $log[] = $res ? "[WARN]  mysql: ".trim($res) : "[INFO]  SQL imported into {$tmpDb}.";
                        $log[] = runImportScript($SCRIPT, $PHP_BIN, $tmpDb, $c['host'], $c['user'], $c['pass'],
                                                 $dryRun, $only ?: null, $allLogs, $APP_ROOT, $scEnv);
                    } finally {
                        $pdo->exec("DROP DATABASE IF EXISTS `{$tmpDb}`");
                        $log[] = "[INFO]  Dropped temp DB: {$tmpDb}";
                    }
                    $out = implode("\n", $log);
                }
                echo json_encode(['ok'=>true, 'output'=>$out]);
                exit;

            // ── DB status ─────────────────────────────────────────────────
            case 'status':
                $c   = dbCreds();
                $db  = trim($_POST['db_name'] ?? 'sc');
                $pdo = makePdo($c['host'], $c['port'], $c['user'], $c['pass'], $db);
                $tables = ['users','doctors','patients','encounters','prescriptions',
                           'prescription_items','bills','payments','clinical_notes','activity_logs',
                           'medicines','openemr_import_map'];
                $rows = [];
                foreach ($tables as $t) {
                    try {
                        $cnt = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
                        $rows[] = ['table'=>$t, 'rows'=>(int)$cnt];
                    } catch (\Throwable $e) {
                        $rows[] = ['table'=>$t, 'rows'=>null, 'missing'=>true];
                    }
                }
                echo json_encode(['ok'=>true, 'tables'=>$rows]);
                exit;
        }
    } catch (\Throwable $e) {
        echo json_encode(['ok'=>false, 'output'=>'[ERR]   '.$e->getMessage()]);
        exit;
    }
    echo json_encode(['ok'=>false, 'output'=>'[ERR]   Unknown action.']);
    exit;
}

// ── PHP helpers ───────────────────────────────────────────────────────────────
function dbCreds(): array {
    return [
        'host' => trim($_POST['db_host'] ?? '127.0.0.1'),
        'port' => trim($_POST['db_port'] ?? '3306'),
        'user' => trim($_POST['db_user'] ?? 'root'),
        'pass' => $_POST['db_pass'] ?? '',
    ];
}
function makePdo(string $h, string $p, string $u, string $pw, ?string $db): PDO {
    $dsn = "mysql:host={$h};port={$p};charset=utf8mb4" . ($db ? ";dbname={$db}" : '');
    return new PDO($dsn, $u, $pw, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}
function findBin(string $name): string {
    $p = trim(shell_exec("which {$name} 2>/dev/null") ?: '');
    return $p ?: "/usr/bin/{$name}";
}
function runProc(array $cmd, string $cwd, array $env=[]): string {
    $d = [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']];
    $proc = proc_open($cmd, $d, $pipes, $cwd, $env ?: null);
    if (!is_resource($proc)) return 'proc_open failed';
    fclose($pipes[0]);
    $o = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    proc_close($proc);
    return $o;
}
function runImportScript(string $script, string $phpBin, string $emrDb, string $emrHost,
                         string $emrUser, string $emrPass, bool $dryRun, ?string $only,
                         bool $allLogs, string $cwd, array $env): string {
    $args = [$phpBin, $script,
        "--openemr-db={$emrDb}", "--openemr-host={$emrHost}",
        "--openemr-user={$emrUser}", "--openemr-pass={$emrPass}"];
    if ($dryRun)  $args[] = '--dry-run';
    if ($only)    $args[] = "--only={$only}";
    if ($allLogs) $args[] = '--all-logs';
    return runProc($args, $cwd, $env);
}
function resolveSchemaFile(array &$log): ?string {
    if (!empty($_FILES['schema_file']['tmp_name'])) {
        $log[] = "[INFO]  Using uploaded schema: " . htmlspecialchars($_FILES['schema_file']['name']);
        return $_FILES['schema_file']['tmp_name'];
    }
    $path = trim($_POST['schema_path'] ?? '');
    if ($path && file_exists($path)) {
        $log[] = "[INFO]  Using schema from server path: {$path}";
        return $path;
    }
    if ($path) $log[] = "[WARN]  Schema path not found: {$path} — skipping schema import.";
    return null;
}
function runMigrateWithRetry(string $phpBin, string $artisan, string $cwd, array $env,
                             string $dbHost, string $dbPort, string $dbUser, string $dbPass, string $dbName): string {
    $allOut = '';
    for ($attempt = 0; $attempt < 60; $attempt++) {
        $out = runProc([$phpBin, $artisan, 'migrate', '--force', '--no-interaction'], $cwd, $env);
        $allOut .= ($attempt > 0 ? "\n[RETRY #{$attempt}]\n" : '') . $out;
        // Strip ANSI escape codes before parsing
        $plain = preg_replace('/\x1b\[[0-9;]*m/', '', $out);
        // Success: no FAIL lines
        if (!preg_match('/\bFAIL\b/', $plain)) break;
        // Extract the failed migration name from e.g. "  2026_05_02_..._table ... FAIL"
        if (!preg_match('/^\s{2}(\d{4}_\d{2}_\d{2}_\d{6}_\S+)\s+\.+\s+[\d.]+ms\s+FAIL/m', $plain, $m)) break;
        $migName = $m[1];
        // Only auto-skip "already exists" or "not found" (ALTER on missing table) errors
        $isAlreadyExists = str_contains($plain, 'already exists');
        $isNotFound      = str_contains($plain, 'Base table or view not found');
        if (!$isAlreadyExists && !$isNotFound) break;
        // Mark migration as run so Laravel skips it next time
        try {
            $pdo = makePdo($dbHost, $dbPort, $dbUser, $dbPass, $dbName);
            $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, `migration` varchar(255) NOT NULL, `batch` int NOT NULL)");
            $batch = (int)($pdo->query("SELECT COALESCE(MAX(batch),0) FROM migrations")->fetchColumn()) + 1;
            $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (migration, batch) VALUES (?, ?)");
            $stmt->execute([$migName, $batch]);
            $allOut .= "\n[INFO]  Skipped already-existing migration: {$migName}";
        } catch (\Throwable $e) {
            $allOut .= "\n[ERR]   Could not mark migration: " . $e->getMessage();
            break;
        }
    }
    return $allOut;
}
function setEnvVal(string $env, string $key, string $val): string {
    $val = str_contains($val, ' ') ? "\"{$val}\"" : $val;
    if (preg_match("/^{$key}=/m", $env)) {
        return preg_replace("/^{$key}=.*/m", "{$key}={$val}", $env);
    }
    return $env . "\n{$key}={$val}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SpecialityCare — Setup & Import</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--navy:#0f1f3d;--teal:#0ab4b4;--teal-g:rgba(10,180,180,.12);--gold:#f5a623;
      --red:#e84c4c;--green:#27c47a;--bg:#f0f4f8;--border:#e2e8f0;--muted:#64748b;
      --radius:10px;--shadow:0 2px 12px rgba(15,31,61,.08)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:var(--bg);color:#1a2942;font-size:14px}
.wrap{max-width:980px;margin:0 auto;padding:24px 16px}
.danger-bar{background:#fff3cd;border:1px solid #f5a623;border-radius:var(--radius);
            padding:12px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:13px}
.danger-bar i{color:var(--gold);font-size:18px;flex-shrink:0}
h1{font-size:22px;font-weight:700;color:var(--navy);margin-bottom:4px}
.sub{color:var(--muted);font-size:13px;margin-bottom:20px}
.tabs{display:flex;gap:4px;border-bottom:2px solid var(--border);margin-bottom:20px}
.tab{padding:9px 20px;border:none;background:none;cursor:pointer;font-size:13.5px;font-weight:600;
     color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-2px;transition:.15s}
.tab.active{color:var(--teal);border-bottom-color:var(--teal)}
.pane{display:none}.pane.active{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.pane.wide{display:none}.pane.wide.active{display:block}
.card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;margin-bottom:16px}
.card-h{padding:12px 16px;background:#f8fafc;border-bottom:1px solid var(--border);
         font-weight:700;font-size:13px;display:flex;align-items:center;gap:8px}
.card-b{padding:16px}
label{display:block;font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;margin-top:10px}
label:first-child{margin-top:0}
input[type=text],input[type=password],select{width:100%;padding:8px 10px;border:1px solid var(--border);
  border-radius:6px;font-size:13px;outline:none;transition:.15s}
input:focus,select:focus{border-color:var(--teal);box-shadow:0 0 0 3px var(--teal-g)}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.btn{padding:9px 18px;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;
     transition:.15s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:var(--teal);color:#fff}.btn-primary:hover{background:#089898}
.btn-outline{background:#fff;color:var(--navy);border:1.5px solid var(--border)}.btn-outline:hover{border-color:var(--teal);color:var(--teal)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn:disabled{opacity:.5;cursor:not-allowed}
.mt{margin-top:12px}.mt2{margin-top:8px}
.src-cards{display:flex;gap:8px;margin-top:6px}
.src-card{flex:1;border:2px solid var(--border);border-radius:8px;padding:10px 12px;cursor:pointer;transition:.15s}
.src-card:hover,.src-card.sel{border-color:var(--teal);background:var(--teal-g)}
.src-card .title{font-weight:700;font-size:12.5px}.src-card .desc{font-size:11px;color:var(--muted)}
.src-section{display:none;margin-top:10px}.src-section.show{display:block}
.check-row{display:flex;align-items:center;gap:8px;margin-top:10px;font-size:13px;cursor:pointer}
.check-row input{width:15px;height:15px}
/* terminal */
.term{background:#0f1f3d;border-radius:var(--radius);overflow:hidden;margin-top:16px}
.term-bar{background:#162545;padding:7px 14px;display:flex;align-items:center;justify-content:space-between}
.term-dots span{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:5px}
.term-label{font-size:11.5px;color:#8ba0c0}
.term-body{padding:14px;max-height:420px;overflow-y:auto;font-family:monospace;font-size:12px;
           line-height:1.7;white-space:pre-wrap;word-break:break-all;color:#c9d8f0;min-height:80px}
.ti{color:#27c47a}.tw{color:#f5a623}.te{color:#e84c4c}.td{color:#0ab4b4}
.spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(10,180,180,.3);
          border-top-color:var(--teal);border-radius:50%;animation:spin .7s linear infinite;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
/* status table */
.status-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:14px}
.stat-box{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:14px 16px;text-align:center}
.stat-box .num{font-size:22px;font-weight:700;color:var(--teal)}
.stat-box .lbl{font-size:11.5px;color:var(--muted);margin-top:2px}
.stat-box.missing .num{color:var(--red)}
.badge-ok{background:rgba(39,196,122,.12);color:var(--green);padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700}
.badge-no{background:rgba(232,76,76,.12);color:var(--red);padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700}
</style>
</head>
<body>
<div class="wrap">

<div class="danger-bar">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div style="flex:1"><strong>Security notice:</strong> Password gate is the only protection — file still has full DB + shell access.
    <strong>Delete <code>public/setupxx.php</code> immediately after setup is complete.</strong></div>
    <a href="?logout=1" style="background:#fff;color:var(--navy);padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid var(--border);flex-shrink:0">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<h1><i class="fa-solid fa-hospital" style="color:var(--teal)"></i> SpecialityCare — Setup &amp; Import</h1>
<p class="sub">Standalone setup — works without the Laravel app running. Configure your database, import the schema, then import from OpenEMR.</p>

<div class="tabs">
    <button class="tab active" onclick="sw('db')"><i class="fa-solid fa-database"></i> 1. Database Setup</button>
    <button class="tab"        onclick="sw('import')"><i class="fa-solid fa-file-import"></i> 2. OpenEMR Import</button>
    <button class="tab"        onclick="sw('status')"><i class="fa-solid fa-chart-bar"></i> 3. Status</button>
</div>

<!-- ═══ TAB 1: DB Setup ═══════════════════════════════════════════════════ -->
<div id="pane-db" class="pane active">
<div>
<div class="card">
    <div class="card-h"><i class="fa-solid fa-plug"></i> Database Connection</div>
    <div class="card-b">
        <div class="row2">
            <div><label>Host</label><input type="text" id="db-host" value="127.0.0.1"></div>
            <div><label>Port</label><input type="text" id="db-port" value="3306"></div>
        </div>
        <div class="row2 mt2">
            <div><label>User</label><input type="text" id="db-user" value="root"></div>
            <div><label>Password</label><input type="password" id="db-pass" value=""></div>
        </div>
        <label class="mt">Target Database Name</label>
        <input type="text" id="db-name" value="sc">
        <div class="mt" style="display:flex;gap:8px">
            <button class="btn btn-outline btn-sm" onclick="testDb()"><i class="fa-solid fa-plug"></i> Test Connection</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-h"><i class="fa-solid fa-table"></i> Schema Setup</div>
    <div class="card-b">
        <div style="background:rgba(245,166,35,.08);border:1px solid rgba(245,166,35,.35);border-radius:7px;padding:10px 13px;margin-bottom:12px;font-size:12.5px">
            <strong>Schema SQL</strong> = the <em>SpecialityCare</em> blank schema dump (e.g. <code>sc-nodata.sql</code>).<br>
            <span style="color:var(--red)">Do NOT use the OpenEMR dump here</span> — that goes on the Import tab.
            Leave blank to let <code>artisan migrate</code> create all tables from scratch (recommended for fresh install).
        </div>
        <label>Upload SC Schema SQL (optional)</label>
        <input type="file" id="schema-file" accept=".sql,.gz" style="width:100%;padding:6px;font-size:13px">
        <label class="mt">— or — Server path to SC schema SQL</label>
        <input type="text" id="schema-path" placeholder="/home/xxx/Downloads/sc-nodata.sql">
        <label class="check-row mt" style="color:var(--red)">
            <input type="checkbox" id="drop-first">
            <span><strong>Drop &amp; recreate database first</strong> — use this to fix a corrupted/wrong schema. <em>Deletes all existing data!</em></span>
        </label>
        <div class="mt" style="display:flex;gap:8px">
            <button class="btn btn-primary" onclick="setupDb()"><i class="fa-solid fa-play"></i> Setup Database</button>
        </div>
    </div>
</div>
</div>

<div>
<div class="term">
    <div class="term-bar">
        <div class="term-dots"><span style="background:#e84c4c"></span><span style="background:#f5a623"></span><span style="background:#27c47a"></span></div>
        <span class="term-label" id="db-label">Output appears here</span>
    </div>
    <div class="term-body" id="db-out">Run a step above to see output.</div>
</div>
<div class="card mt" style="margin-top:16px">
    <div class="card-h"><i class="fa-solid fa-circle-info"></i> What Setup Does</div>
    <div class="card-b" style="font-size:12.5px;color:var(--muted);line-height:1.8">
        <ol style="padding-left:18px">
            <li>Creates the target database if it doesn't exist</li>
            <li>Imports your schema SQL (all tables, no data)</li>
            <li>Updates <code>.env</code> with the new credentials</li>
            <li>Runs <code>artisan migrate --force</code> if artisan is available</li>
        </ol>
        <p style="margin-top:10px">After setup, proceed to the <strong>OpenEMR Import</strong> tab.</p>
    </div>
</div>
</div>
</div>

<!-- ═══ TAB 2: Import ════════════════════════════════════════════════════ -->
<div id="pane-import" class="pane">
<div>
<div class="card">
    <div class="card-h"><i class="fa-solid fa-database"></i> Target SC Database</div>
    <div class="card-b">
        <div class="row2">
            <div><label>Host</label><input type="text" id="i-db-host" value="127.0.0.1"></div>
            <div><label>Port</label><input type="text" id="i-db-port" value="3306"></div>
        </div>
        <div class="row2 mt2">
            <div><label>User</label><input type="text" id="i-db-user" value="root"></div>
            <div><label>Password</label><input type="password" id="i-db-pass" value=""></div>
        </div>
        <label class="mt">Database</label>
        <input type="text" id="i-db-name" value="sc">
    </div>
</div>

<div class="card">
    <div class="card-h"><i class="fa-solid fa-play"></i> OpenEMR Data Source</div>
    <div class="card-b">
        <label>Source Type</label>
        <div class="src-cards">
            <div class="src-card sel" onclick="selSrc('livedb',this)">
                <div class="title"><i class="fa-solid fa-database"></i> Live DB</div>
                <div class="desc">Connect to open72 directly</div>
            </div>
            <div class="src-card" onclick="selSrc('sqlfile',this)">
                <div class="title"><i class="fa-solid fa-upload"></i> Upload SQL</div>
                <div class="desc">.sql or .sql.gz file</div>
            </div>
            <div class="src-card" onclick="selSrc('serverpath',this)">
                <div class="title"><i class="fa-solid fa-hard-drive"></i> Server Path</div>
                <div class="desc">File already on server</div>
            </div>
        </div>

        <div id="src-livedb" class="src-section show">
            <div class="row2">
                <div><label>OpenEMR DB</label><input type="text" id="emr-db" value="open72"></div>
                <div><label>Host</label><input type="text" id="emr-host" value="127.0.0.1"></div>
            </div>
            <div class="row2 mt2">
                <div><label>User</label><input type="text" id="emr-user" value="root"></div>
                <div><label>Password</label><input type="password" id="emr-pass" value=""></div>
            </div>
        </div>
        <div id="src-sqlfile" class="src-section">
            <label>SQL File (.sql or .sql.gz)</label>
            <input type="file" id="i-sqlfile" accept=".sql,.gz,.sql.gz" style="width:100%;padding:6px;font-size:13px">
        </div>
        <div id="src-serverpath" class="src-section">
            <label>Full Server Path</label>
            <input type="text" id="i-serverpath" placeholder="/home/xxx/Downloads/open72.sql.gz">
        </div>

        <div class="row2 mt">
            <div>
                <label>Module</label>
                <select id="i-only" onchange="document.getElementById('all-logs-wrap').style.display=(this.value===''||this.value==='logs')?'':'none'">
                    <option value="">All modules</option>
                    <option value="patients">Patients</option>
                    <option value="encounters">Encounters</option>
                    <option value="prescriptions">Prescriptions</option>
                    <option value="billing">Billing</option>
                    <option value="clinical_notes">Clinical Notes</option>
                    <option value="logs">Activity Logs</option>
                </select>
            </div>
            <div style="padding-top:20px">
                <label class="check-row"><input type="checkbox" id="i-dryrun"> Dry run (no writes)</label>
            </div>
        </div>
        <div id="all-logs-wrap" style="display:none;margin-top:8px;padding:10px 12px;background:rgba(245,166,35,.07);border:1px solid rgba(245,166,35,.3);border-radius:8px;font-size:12.5px">
            <label class="check-row" style="margin:0"><input type="checkbox" id="i-alllogs">
            Import ALL log events (incl. read-only, ~3.6M rows). Default: meaningful actions only (~120K).</label>
        </div>

        <button class="btn btn-primary mt" onclick="runImport()"><i class="fa-solid fa-play"></i> Start Import</button>
    </div>
</div>
</div>

<div>
<div class="term">
    <div class="term-bar">
        <div class="term-dots"><span style="background:#e84c4c"></span><span style="background:#f5a623"></span><span style="background:#27c47a"></span></div>
        <span class="term-label" id="i-label">Import output</span>
    </div>
    <div class="term-body" id="i-out">Start an import to see output here.</div>
</div>
</div>
</div>

<!-- ═══ TAB 3: Status ════════════════════════════════════════════════════ -->
<div id="pane-status" class="pane wide">
<div class="card">
    <div class="card-h"><i class="fa-solid fa-chart-bar"></i> Database Status</div>
    <div class="card-b">
        <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <label>Database</label><input type="text" id="s-db-name" value="sc">
            </div>
            <button class="btn btn-outline btn-sm" style="margin-bottom:1px" onclick="loadStatus()"><i class="fa-solid fa-rotate"></i> Refresh</button>
        </div>
        <div id="status-out" class="status-grid" style="margin-top:14px">
            <p style="color:var(--muted);font-size:13px;grid-column:1/-1">Click Refresh to load table counts.</p>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-h"><i class="fa-solid fa-circle-info"></i> Next Steps After Setup</div>
    <div class="card-b" style="font-size:13px;line-height:1.9;color:var(--muted)">
        <ol style="padding-left:18px">
            <li>Verify table counts above look correct</li>
            <li>Open the main app at <a href="/specialitycare/" style="color:var(--teal)">/specialitycare/</a> and log in as <strong>demo@specialitycare.co.in</strong> / <strong>Admin@123</strong></li>
            <li>Head to <a href="/specialitycare/import" style="color:var(--teal)">/specialitycare/import</a> for future imports via the web UI</li>
            <li><strong style="color:var(--red)">Delete this file:</strong> <code>public/setupxx.php</code></li>
        </ol>
    </div>
</div>
</div>

</div><!-- /wrap -->

<script>
const SELF = '<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>';

// ── Tabs ──────────────────────────────────────────────────────────────────
function sw(name) {
    document.querySelectorAll('.tab,.pane,.pane.wide').forEach(el => el.classList.remove('active'));
    document.querySelector(`.tab[onclick="sw('${name}')"]`).classList.add('active');
    document.getElementById('pane-' + name).classList.add('active');
    if (name === 'status') loadStatus();
}

// ── Source type selector ──────────────────────────────────────────────────
let curSrc = 'livedb';
function selSrc(val, el) {
    curSrc = val;
    document.querySelectorAll('.src-card').forEach(c => c.classList.remove('sel'));
    el.classList.add('sel');
    ['livedb','sqlfile','serverpath'].forEach(s => {
        document.getElementById('src-' + s).classList.toggle('show', s === val);
    });
}

// ── Generic fetch helper (always JSON back) ───────────────────────────────
async function post(action, fd) {
    const r = await fetch(SELF + '?action=' + action, { method:'POST', body:fd });
    const t = await r.text();
    try { return JSON.parse(t); }
    catch(_) { return { ok:false, output:'[ERR]   Server error (HTTP '+r.status+'):\n'
                                       + t.replace(/<[^>]+>/g,' ').replace(/\s{2,}/g,' ').trim().slice(0,600) }; }
}

// ── Test DB connection ────────────────────────────────────────────────────
async function testDb() {
    const fd = dbFd();
    setOut('db', 'Testing connection…', true);
    const r = await post('test-db', fd);
    setOut('db', r.output || (r.ok ? 'OK' : 'Failed'), false);
}

// ── Setup DB ─────────────────────────────────────────────────────────────
async function setupDb() {
    const dropFirst = document.getElementById('drop-first').checked;
    if (dropFirst && !confirm('This will DROP the database and delete ALL data. Are you sure?')) return;
    const fd = dbFd();
    const sf = document.getElementById('schema-file').files[0];
    if (sf) fd.append('schema_file', sf);
    fd.append('schema_path', document.getElementById('schema-path').value);
    if (dropFirst) fd.append('drop_first', '1');
    setOut('db', 'Setting up database…', true);
    const r = await post('setup-db', fd);
    setOut('db', r.output, false);
}

// ── Run import ────────────────────────────────────────────────────────────
async function runImport() {
    const fd = new FormData();
    // SC DB creds
    fd.append('db_host', document.getElementById('i-db-host').value);
    fd.append('db_port', document.getElementById('i-db-port').value);
    fd.append('db_user', document.getElementById('i-db-user').value);
    fd.append('db_pass', document.getElementById('i-db-pass').value);
    fd.append('db_name', document.getElementById('i-db-name').value);
    fd.append('source_type', curSrc);
    fd.append('dry_run',  document.getElementById('i-dryrun').checked ? '1' : '');
    fd.append('all_logs', document.getElementById('i-alllogs').checked ? '1' : '');
    const only = document.getElementById('i-only').value;
    if (only) fd.append('only_module', only);

    if (curSrc === 'livedb') {
        fd.append('openemr_db',   document.getElementById('emr-db').value);
        fd.append('openemr_host', document.getElementById('emr-host').value);
        fd.append('openemr_user', document.getElementById('emr-user').value);
        fd.append('openemr_pass', document.getElementById('emr-pass').value);
    } else if (curSrc === 'sqlfile') {
        const f = document.getElementById('i-sqlfile').files[0];
        if (!f) { alert('Please select a SQL file.'); return; }
        fd.append('sql_file', f);
    } else {
        fd.append('server_path', document.getElementById('i-serverpath').value);
    }

    setOut('i', 'Running import…', true);
    const started = Date.now();
    const r = await post('run-import', fd);
    const secs = ((Date.now()-started)/1000).toFixed(1);
    setOut('i', r.output, false, secs);
}

// ── Status ────────────────────────────────────────────────────────────────
async function loadStatus() {
    const fd = new FormData();
    fd.append('db_host', document.getElementById('db-host').value);
    fd.append('db_port', document.getElementById('db-port').value);
    fd.append('db_user', document.getElementById('db-user').value);
    fd.append('db_pass', document.getElementById('db-pass').value);
    fd.append('db_name', document.getElementById('s-db-name').value);
    const r = await post('status', fd);
    const el = document.getElementById('status-out');
    if (!r.ok || !r.tables) { el.innerHTML='<p style="color:var(--red)">'+ansi(r.output||'Error')+'</p>'; return; }
    el.innerHTML = r.tables.map(t => `
        <div class="stat-box ${t.missing?'missing':''}">
            <div class="num">${t.missing ? '✗' : t.rows.toLocaleString()}</div>
            <div class="lbl">${t.table}</div>
            ${t.missing ? '' : ''}
        </div>`).join('');
}

// ── Helpers ───────────────────────────────────────────────────────────────
function dbFd() {
    const fd = new FormData();
    fd.append('db_host', document.getElementById('db-host').value);
    fd.append('db_port', document.getElementById('db-port').value);
    fd.append('db_user', document.getElementById('db-user').value);
    fd.append('db_pass', document.getElementById('db-pass').value);
    fd.append('db_name', document.getElementById('db-name').value);
    return fd;
}
function setOut(prefix, text, loading, secs) {
    const el = document.getElementById(prefix+'-out');
    const lb = document.getElementById(prefix+'-label');
    if (loading) {
        el.innerHTML = '<span style="color:#8ba0c0"><span class="spinner"></span> Please wait…</span>';
        if (lb) lb.textContent = 'Running…';
    } else {
        el.innerHTML = ansi(text || '(no output)');
        el.scrollTop = el.scrollHeight;
        if (lb) lb.textContent = secs ? `Done in ${secs}s` : 'Complete';
    }
}
function ansi(raw) {
    const e = raw.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    return e.replace(/\x1b\[0m/g,'</span>')
            .replace(/\x1b\[32m/g,'<span class="ti">')
            .replace(/\x1b\[33m/g,'<span class="tw">')
            .replace(/\x1b\[31m/g,'<span class="te">')
            .replace(/\x1b\[36m/g,'<span class="td">')
            .replace(/\x1b\[[0-9;]+m/g,'');
}
</script>
</body>
</html>
