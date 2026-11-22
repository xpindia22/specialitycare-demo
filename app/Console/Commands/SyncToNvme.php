<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

class SyncToNvme extends Command
{
    protected $signature   = 'sync:to-nvme {--dry-run : Preview what would be pushed without writing} {--db= : Push this database instead of the app default (e.g. phpbb, open72, btlp) — same name is assumed on both sides} {--tables= : Comma-separated list of tables to push, instead of every shared table}';
    protected $description = 'Push new/updated local records to nvme production DB (insert new, update changed)';

    const SKIP = [
        'cache', 'cache_locks', 'sessions',
        'jobs', 'failed_jobs', 'job_batches',
        'password_reset_tokens', 'migrations',
        // Per-installation UI configuration — see SyncFromNvme::SKIP for why.
        'clinical_chips',
    ];

    public function handle(): int
    {
        $dryRun    = $this->option('dry-run');
        $sshHost   = config('nvme.ssh_host');
        $sshUser   = config('nvme.ssh_user');
        $sshPass   = config('nvme.ssh_pass');
        $dbName    = $this->option('db') ?: config('nvme.db_name');
        $dbUser    = config('nvme.db_user');
        $dbPass    = config('nvme.db_pass');
        $localPort = 3307;

        // --db=X targets a different local database than this app's own
        // (specialitycare) connection — same MySQL server/credentials, just a
        // different schema, so a plain USE switches it for this connection.
        if ($this->option('db')) {
            DB::statement('USE `' . $dbName . '`');
        }

        exec("lsof -ti TCP:{$localPort} 2>/dev/null | xargs -r kill -9 2>/dev/null");
        usleep(300000);

        $tunnelCmd = sprintf(
            'sshpass -p %s ssh -N -f -o StrictHostKeyChecking=no -o ServerAliveInterval=30 -L %d:127.0.0.1:3306 %s@%s 2>/dev/null',
            escapeshellarg($sshPass), $localPort, escapeshellarg($sshUser), escapeshellarg($sshHost)
        );
        exec($tunnelCmd, $out, $rc);
        if ($rc !== 0) {
            $this->error("Could not open SSH tunnel (exit {$rc}).");
            return self::FAILURE;
        }
        sleep(2);

        $tunnelPid = trim(shell_exec("lsof -ti TCP:{$localPort} 2>/dev/null") ?? '');

        try {
            $remote = new PDO(
                "mysql:host=127.0.0.1;port={$localPort};dbname={$dbName};charset=utf8mb4",
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
            );
        } catch (PDOException $e) {
            $this->error('Cannot connect to remote DB via tunnel: ' . $e->getMessage());
            if ($tunnelPid) exec("kill {$tunnelPid} 2>/dev/null");
            return self::FAILURE;
        }

        $this->info($dryRun ? '[DRY RUN] Checking what would be pushed...' : 'Starting sync to nvme...');

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
                iterator_to_array((function () use ($remote, $dbName) {
                    foreach ($remote->query("SHOW TABLES FROM `{$dbName}`") as $r) yield $r;
                })())
            ));

            $remote->exec('SET FOREIGN_KEY_CHECKS=0');

            $onlyTables = $this->option('tables')
                ? array_map('trim', explode(',', $this->option('tables')))
                : null;

            foreach ($localTables as $table) {
                if (in_array($table, self::SKIP)) continue;
                if ($onlyTables !== null && !in_array($table, $onlyTables, true)) continue;
                if (!isset($remoteTables[$table])) {
                    $this->warn("  skip (missing on remote): {$table}");
                    continue;
                }

                $localCols  = array_column(DB::select("SHOW COLUMNS FROM `{$table}`"), 'Field');
                if (!in_array('id', $localCols)) continue;

                $remoteCols   = $remote->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
                $sharedCols   = array_values(array_intersect($localCols, $remoteCols));
                $hasUpdatedAt = in_array('updated_at', $sharedCols);

                $remoteMax = (int) $remote->query("SELECT COALESCE(MAX(id),0) FROM `{$table}`")->fetchColumn();

                // ── 1a. Fill ID gaps: local rows below remote_max missing remotely
                $gapStart  = max(1, $remoteMax - 2000);
                $localIds  = DB::table($table)->whereBetween('id', [$gapStart, $remoteMax])->pluck('id')->toArray();
                $remoteIds = $remote->query("SELECT id FROM `{$table}` WHERE id >= {$gapStart} AND id <= {$remoteMax}")->fetchAll(PDO::FETCH_COLUMN);
                $gapIds    = array_values(array_diff($localIds, $remoteIds));

                if (!empty($gapIds)) {
                    $colList = implode(',', array_map(fn($c) => "`{$c}`", $sharedCols));
                    $rowPh   = '(' . implode(',', array_fill(0, count($sharedCols), '?')) . ')';
                    if ($dryRun) {
                        $this->line("  {$table}: " . count($gapIds) . " gap row(s) would be inserted");
                        $totalInserted += count($gapIds);
                    } else {
                        foreach (array_chunk($gapIds, 100) as $chunk) {
                            $rows = DB::table($table)->whereIn('id', $chunk)->get()
                                ->map(fn($r) => array_intersect_key((array)$r, array_flip($sharedCols)))->toArray();
                            if (empty($rows)) continue;
                            $allPh = implode(',', array_fill(0, count($rows), $rowPh));
                            $flat  = array_merge(...array_map(fn($r) => array_values($r), $rows));
                            $remote->prepare("INSERT IGNORE INTO `{$table}` ({$colList}) VALUES {$allPh}")->execute($flat);
                            $totalInserted += count($rows);
                        }
                    }
                }

                // ── 1b. INSERT new rows (id > remote max) ────────────────────
                $newCount = DB::table($table)->where('id', '>', $remoteMax)->count();

                if ($newCount > 0) {
                    if ($dryRun) {
                        $this->line("  {$table}: {$newCount} row(s) would be inserted");
                        $totalInserted += $newCount;
                    } else {
                        $colList = implode(',', array_map(fn($c) => "`{$c}`", $sharedCols));
                        $rowPh   = '(' . implode(',', array_fill(0, count($sharedCols), '?')) . ')';

                        DB::table($table)
                            ->where('id', '>', $remoteMax)
                            ->orderBy('id')
                            ->chunk(100, function ($chunk) use ($remote, $table, $sharedCols, $colList, $rowPh, &$totalInserted) {
                                $rows = array_map(
                                    fn($r) => array_intersect_key((array) $r, array_flip($sharedCols)),
                                    $chunk->toArray()
                                );
                                if (empty($rows)) return;

                                $allPh = implode(',', array_fill(0, count($rows), $rowPh));
                                $flat  = array_merge(...array_map(fn($r) => array_values($r), $rows));
                                $remote->prepare("INSERT IGNORE INTO `{$table}` ({$colList}) VALUES {$allPh}")
                                       ->execute($flat);
                                $totalInserted += count($rows);
                            });
                    }
                }

                // ── 2. UPDATE existing rows where local.updated_at is newer ──
                if (!$hasUpdatedAt || $remoteMax === 0) continue;

                $updatedCount = 0;
                $batchSize    = 500;
                $offset       = 0;

                while (true) {
                    // Get remote timestamps in batches
                    $remoteStamps = $remote->query(
                        "SELECT id, updated_at FROM `{$table}` WHERE id <= {$remoteMax} ORDER BY id LIMIT {$batchSize} OFFSET {$offset}"
                    )->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($remoteStamps)) break;

                    $ids = array_column($remoteStamps, 'id');
                    $localRows = DB::table($table)->whereIn('id', $ids)->pluck('updated_at', 'id');

                    $staleIds = [];
                    foreach ($remoteStamps as $row) {
                        $localTs  = strtotime($localRows[$row['id']] ?? '');
                        $remoteTs = strtotime($row['updated_at'] ?? '');
                        if ($localTs && $localTs > $remoteTs) {
                            $staleIds[] = $row['id'];
                        }
                    }

                    if (!empty($staleIds)) {
                        if ($dryRun) {
                            $updatedCount += count($staleIds);
                        } else {
                            $freshRows = DB::table($table)->whereIn('id', $staleIds)->get();
                            $updateCols = array_values(array_filter($sharedCols, fn($c) => $c !== 'id'));
                            $setParts   = array_map(fn($c) => "`{$c}` = ?", $updateCols);
                            $setSql     = implode(', ', $setParts);

                            foreach ($freshRows as $fresh) {
                                $fresh = (array) $fresh;
                                $vals  = array_values(array_map(fn($c) => $fresh[$c] ?? null, $updateCols));
                                $vals[] = $fresh['id'];
                                try {
                                    $remote->prepare("UPDATE `{$table}` SET {$setSql} WHERE id = ?")
                                           ->execute($vals);
                                    $updatedCount++;
                                } catch (\Throwable $e) {
                                    $this->warn("  skip update {$table} id={$fresh['id']}: " . $e->getMessage());
                                }
                            }
                        }
                    }

                    $offset += $batchSize;
                    if (count($remoteStamps) < $batchSize) break;
                }

                if ($newCount > 0 || $updatedCount > 0) {
                    $parts = [];
                    if ($newCount > 0)     $parts[] = "+{$newCount} new";
                    if ($updatedCount > 0) $parts[] = "~{$updatedCount} updated";
                    $this->info("  {$table}: " . implode(', ', $parts));
                    $tablesSynced++;
                    $totalUpdated += $updatedCount;
                }
            }

            $remote->exec('SET FOREIGN_KEY_CHECKS=1');

            $this->info($dryRun
                ? "Dry run done. {$totalInserted} insert(s), {$totalUpdated} update(s) would be pushed."
                : "Sync complete — {$totalInserted} inserted, {$totalUpdated} updated across {$tablesSynced} table(s)."
            );

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $remote->exec('SET FOREIGN_KEY_CHECKS=1');
            $this->error('Sync error: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            if ($tunnelPid) exec("kill {$tunnelPid} 2>/dev/null");
        }
    }
}
