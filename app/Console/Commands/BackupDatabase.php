<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature   = 'specialitycare:backup {--keep=30 : Keep last N daily dumps}';
    protected $description = 'Dump the MySQL database to storage/app/backups/, gzip it, and prune older than --keep days';

    public function handle(): int
    {
        $cfg  = config('database.connections.' . config('database.default'));
        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? '3306';
        $db   = $cfg['database'] ?? '';
        $user = $cfg['username'] ?? '';
        $pass = $cfg['password'] ?? '';

        if (!$db) { $this->error('No database name configured.'); return self::FAILURE; }

        $dir = storage_path('app/backups');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $stamp = now()->format('Ymd_His');
        $file  = "{$dir}/db-{$db}-{$stamp}.sql.gz";

        // Build mysqldump command. We pass password via MYSQL_PWD env to avoid leaking on argv.
        $cmd = sprintf(
            'mysqldump --single-transaction --quick --routines --triggers --no-tablespaces -h %s -P %s -u %s %s | gzip > %s',
            escapeshellarg($host), escapeshellarg((string) $port), escapeshellarg($user),
            escapeshellarg($db), escapeshellarg($file)
        );

        $process = Process::fromShellCommandline($cmd, base_path(), ['MYSQL_PWD' => $pass]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('mysqldump failed: ' . trim($process->getErrorOutput()));
            return self::FAILURE;
        }

        $size = filesize($file);
        $this->info("Backup written: {$file} (" . $this->humanSize($size) . ')');

        $keep = (int) $this->option('keep');
        $cutoff = now()->subDays($keep);
        $pruned = 0;
        foreach (glob($dir . '/db-*.sql.gz') as $f) {
            $mtime = \Carbon\Carbon::createFromTimestamp(filemtime($f));
            if ($mtime->lessThan($cutoff)) { @unlink($f); $pruned++; }
        }
        if ($pruned) $this->info("Pruned {$pruned} old backup(s) older than {$keep} days.");

        return self::SUCCESS;
    }

    private function humanSize(int $b): string
    {
        $u = ['B','KB','MB','GB'];
        $i = 0;
        while ($b >= 1024 && $i < count($u) - 1) { $b /= 1024; $i++; }
        return number_format($b, 1) . ' ' . $u[$i];
    }
}
