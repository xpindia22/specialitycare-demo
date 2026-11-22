<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

class SyncFromNvme extends Command
{
    protected $signature   = 'sync:from-nvme {--dry-run : Preview what would be pulled without writing} {--db= : Sync this database instead of the app default (e.g. phpbb, open72, btlp) — same name is assumed on both sides unless --remote-db is also given} {--remote-db= : Remote (nvme) database name, when it differs from the local --db name (e.g. local specialitycare_demo vs remote specialitycarecoin)}';
    protected $description = 'Pull new/updated nvme production records into local DB (insert-only for new, update for changed)';

    const SKIP = [
        'cache', 'cache_locks', 'sessions',
        'jobs', 'failed_jobs', 'job_batches',
        'password_reset_tokens', 'migrations',
        // Per-installation UI configuration, not shared patient/clinical
        // records — the gap-fill logic below (designed to recover genuinely
        // missed inserts) can't tell a deliberate local deletion apart from
        // a row that was never synced, so it kept silently re-inserting any
        // clinical chip deleted via Settings within a few minutes.
        'clinical_chips',
    ];

    public function handle(): int
    {
        $dryRun    = $this->option('dry-run');
        $sshHost   = config('nvme.ssh_host');
        $sshUser   = config('nvme.ssh_user');
        $sshPass   = config('nvme.ssh_pass');
        $sshPort   = config('nvme.ssh_port', 22);
        $sshKey    = config('nvme.ssh_key');
        $localDbName  = $this->option('db') ?: config('nvme.db_name');
        $remoteDbName = $this->option('remote-db') ?: $localDbName;
        $dbUser    = config('nvme.db_user');
        $dbPass    = config('nvme.db_pass');
        $localPort = 3307;

        // --db=X targets a different local database than this app's own
        // (specialitycare) connection — same MySQL server/credentials, just a
        // different schema, so a plain USE switches it for this connection.
        // --remote-db=Y lets the nvme-side name differ from the local one
        // (e.g. local specialitycare_demo vs remote specialitycarecoin).
        if ($this->option('db')) {
            DB::statement('USE `' . $localDbName . '`');
        }

        exec("lsof -ti TCP:{$localPort} 2>/dev/null | xargs -r kill -9 2>/dev/null");
        usleep(300000);

        // nvme was hardened 2026-08-15: key-only auth on port 44, password
        // auth removed. Use key-based tunnel when a key is configured
        // (sg89 and onward); fall back to the legacy sshpass/password path
        // otherwise for any host still configured the old way.
        if ($sshKey) {
            $tunnelCmd = sprintf(
                'ssh -N -f -p %d -i %s -o StrictHostKeyChecking=accept-new -o BatchMode=yes -o ServerAliveInterval=30 -L %d:127.0.0.1:3306 %s@%s 2>/dev/null',
                $sshPort, escapeshellarg($sshKey), $localPort, escapeshellarg($sshUser), escapeshellarg($sshHost)
            );
        } else {
            $tunnelCmd = sprintf(
                'sshpass -p %s ssh -N -f -p %d -o StrictHostKeyChecking=no -o ServerAliveInterval=30 -L %d:127.0.0.1:3306 %s@%s 2>/dev/null',
                escapeshellarg($sshPass), $sshPort, $localPort, escapeshellarg($sshUser), escapeshellarg($sshHost)
            );
        }
        exec($tunnelCmd, $out, $rc);
        if ($rc !== 0) {
            $this->error("Could not open SSH tunnel (exit {$rc}).");
            return self::FAILURE;
        }
        sleep(2);

        $tunnelPid = trim(shell_exec("lsof -ti TCP:{$localPort} 2>/dev/null") ?? '');

        try {
            $remote = new PDO(
                "mysql:host=127.0.0.1;port={$localPort};dbname={$remoteDbName};charset=utf8mb4",
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
            );
        } catch (PDOException $e) {
            $this->error('Cannot connect to remote DB via tunnel: ' . $e->getMessage());
            if ($tunnelPid) exec("kill {$tunnelPid} 2>/dev/null");
            return self::FAILURE;
        }

        $this->info($dryRun ? '[DRY RUN] Checking what would be pulled...' : 'Pulling from nvme to local...');

        $totalInserted = 0;
        $totalUpdated  = 0;
        $tablesSynced  = 0;

        try {
            $localTables = array_map(
                fn($r) => array_values((array) $r)[0],
                DB::select('SHOW TABLES')
            );

            $remoteTables = array_flip(array_map(
                fn($r) => array_values((array) $r)[0],
                iterator_to_array((function () use ($remote, $remoteDbName) {
                    foreach ($remote->query("SHOW TABLES FROM `{$remoteDbName}`") as $r) yield $r;
                })())
            ));

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($localTables as $table) {
                if (in_array($table, self::SKIP)) continue;
                if (!isset($remoteTables[$table])) continue;

                $localCols  = array_column(DB::select("SHOW COLUMNS FROM `{$table}`"), 'Field');
                if (!in_array('id', $localCols)) continue;

                $remoteCols  = $remote->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
                $sharedCols  = array_values(array_intersect($remoteCols, $localCols));
                $hasUpdatedAt = in_array('updated_at', $sharedCols);

                $localMax = (int) DB::table($table)->max('id') ?: 0;

                // ── 1a. Fill ID gaps: remote rows below local_max missing locally
                $gapStart  = max(1, $localMax - 2000);
                $remoteIds = $remote->query("SELECT id FROM `{$table}` WHERE id >= {$gapStart} AND id <= {$localMax}")->fetchAll(PDO::FETCH_COLUMN);
                $localIds  = DB::table($table)->whereBetween('id', [$gapStart, $localMax])->pluck('id')->toArray();
                $gapIds    = array_values(array_diff($remoteIds, $localIds));

                if (!empty($gapIds)) {
                    $colList = implode(',', array_map(fn($c) => "`{$c}`", $sharedCols));
                    $rowPh   = '(' . implode(',', array_fill(0, count($sharedCols), '?')) . ')';
                    $colSel  = implode(',', array_map(fn($c) => "`{$c}`", $sharedCols));
                    if ($dryRun) {
                        $this->line("  {$table}: " . count($gapIds) . " gap row(s) would be inserted");
                        $totalInserted += count($gapIds);
                    } else {
                        foreach (array_chunk($gapIds, 100) as $chunk) {
                            $ph   = implode(',', array_fill(0, count($chunk), '?'));
                            $stmt = $remote->prepare("SELECT {$colSel} FROM `{$table}` WHERE id IN ({$ph})");
                            $stmt->execute($chunk);
                            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (empty($rows)) continue;
                            $allPh = implode(',', array_fill(0, count($rows), $rowPh));
                            $flat  = array_merge(...array_map('array_values', $rows));
                            DB::statement("INSERT IGNORE INTO `{$table}` ({$colList}) VALUES {$allPh}", $flat);
                            $totalInserted += count($rows);
                        }
                    }
                }

                // ── 1b. INSERT new rows (id > local max) ─────────────────────
                $newCount = (int) $remote->query("SELECT COUNT(*) FROM `{$table}` WHERE id > {$localMax}")->fetchColumn();

                if ($newCount > 0) {
                    if ($dryRun) {
                        $this->line("  {$table}: {$newCount} row(s) would be inserted");
                        $totalInserted += $newCount;
                    } else {
                        $colList = implode(',', array_map(fn($c) => "`{$c}`", $sharedCols));
                        $rowPh   = '(' . implode(',', array_fill(0, count($sharedCols), '?')) . ')';
                        $colSel  = implode(',', array_map(fn($c) => "`{$c}`", $sharedCols));

                        $offset = 0;
                        $chunk  = 100;
                        while (true) {
                            $stmt = $remote->prepare(
                                "SELECT {$colSel} FROM `{$table}` WHERE id > {$localMax} ORDER BY id LIMIT {$chunk} OFFSET {$offset}"
                            );
                            $stmt->execute();
                            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (empty($rows)) break;

                            $allPh = implode(',', array_fill(0, count($rows), $rowPh));
                            $flat  = array_merge(...array_map('array_values', $rows));
                            DB::statement("INSERT IGNORE INTO `{$table}` ({$colList}) VALUES {$allPh}", $flat);

                            $totalInserted += count($rows);
                            $offset += $chunk;
                            if (count($rows) < $chunk) break;
                        }
                    }
                }

                // ── 2. UPDATE existing rows where nvme.updated_at is newer ───
                if (!$hasUpdatedAt || $localMax === 0) continue;

                // Fetch (id, updated_at) for all existing rows from nvme in batches
                $updatedCount = 0;
                $colSel  = implode(',', array_map(fn($c) => "`{$c}`", $sharedCols));
                $colList = implode(',', array_map(fn($c) => "`{$c}`", $sharedCols));

                $batchSize = 500;
                $offset    = 0;
                while (true) {
                    $stamps = $remote->query(
                        "SELECT id, updated_at FROM `{$table}` WHERE id <= {$localMax} ORDER BY id LIMIT {$batchSize} OFFSET {$offset}"
                    )->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($stamps)) break;

                    $ids = array_column($stamps, 'id');
                    $ph  = implode(',', array_fill(0, count($ids), '?'));

                    // Get local updated_at for same ids
                    $localStamps = DB::table($table)
                        ->whereIn('id', $ids)
                        ->pluck('updated_at', 'id');

                    $staleIds = [];
                    foreach ($stamps as $row) {
                        $remoteTs = strtotime($row['updated_at'] ?? '');
                        $localTs  = strtotime($localStamps[$row['id']] ?? '');
                        if ($remoteTs && $remoteTs > $localTs) {
                            $staleIds[] = $row['id'];
                        }
                    }

                    if (!empty($staleIds)) {
                        if ($dryRun) {
                            $updatedCount += count($staleIds);
                        } else {
                            $idPh  = implode(',', array_fill(0, count($staleIds), '?'));
                            $stmt  = $remote->prepare(
                                "SELECT {$colSel} FROM `{$table}` WHERE id IN ({$idPh})"
                            );
                            $stmt->execute($staleIds);
                            $freshRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($freshRows as $fresh) {
                                $updateCols = array_values(array_filter($sharedCols, fn($c) => $c !== 'id'));
                                $setParts   = array_map(fn($c) => "`{$c}` = ?", $updateCols);
                                $vals       = array_values(array_map(fn($c) => $fresh[$c] ?? null, $updateCols));
                                $vals[]     = $fresh['id'];
                                try {
                                    DB::statement(
                                        "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE id = ?",
                                        $vals
                                    );
                                    $updatedCount++;
                                } catch (\Throwable $e) {
                                    // Skip rows that would violate unique constraints
                                    $this->warn("  skip update {$table} id={$fresh['id']}: " . $e->getMessage());
                                }
                            }
                        }
                    }

                    $offset += $batchSize;
                    if (count($stamps) < $batchSize) break;
                }

                if ($newCount > 0 || $updatedCount > 0) {
                    $parts = [];
                    if ($newCount > 0)    $parts[] = "+{$newCount} new";
                    if ($updatedCount > 0) $parts[] = "~{$updatedCount} updated";
                    $this->info("  {$table}: " . implode(', ', $parts));
                    $tablesSynced++;
                    $totalUpdated += $updatedCount;
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info($dryRun
                ? "Dry run done. {$totalInserted} insert(s), {$totalUpdated} update(s) would be applied."
                : "Pull complete — {$totalInserted} inserted, {$totalUpdated} updated across {$tablesSynced} table(s)."
            );

            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('Sync error: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            if ($tunnelPid) exec("kill {$tunnelPid} 2>/dev/null");
        }
    }
}
