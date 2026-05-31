<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Keep storage/app/public/encounter-reports as a symlink to the external
 * store so the (frequently backed-up) project folder never bloats with the
 * multi-GB OpenEMR PDFs. Auto-heals: if a backup restore (or copy) brings the
 * folder back as a REAL directory, any files not already in the external store
 * are moved there first, then the directory is replaced with the symlink.
 *
 * No-ops where the external store doesn't exist (e.g. production/nvme), so it
 * is safe to run everywhere via the scheduler.
 */
class EnsureEncounterReportsExternal extends Command
{
    protected $signature = 'reports:ensure-external {--dry-run : report actions, change nothing}';
    protected $description = 'Keep encounter-reports symlinked to the external store (auto-heals after restores)';

    public function handle(): int
    {
        $external = rtrim((string) env('ENCOUNTER_REPORTS_EXTERNAL', '/var/www/html/specialitycare-encounter-reports'), '/');
        $link     = storage_path('app/public/encounter-reports');
        $dry      = (bool) $this->option('dry-run');

        if (!is_dir($external)) {
            $this->info("External store not present ({$external}) — nothing to do here.");
            return self::SUCCESS;
        }

        if (is_link($link) && realpath($link) === realpath($external)) {
            $this->info('encounter-reports already symlinked to the external store. ✔');
            return self::SUCCESS;
        }

        // A real directory is sitting where the symlink should be → heal it.
        if (is_dir($link) && !is_link($link)) {
            $moved = $skipped = 0;
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($link, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $file) {
                if (!$file->isFile()) continue;
                $rel  = ltrim(substr($file->getPathname(), strlen($link)), '/');
                $dest = $external . '/' . $rel;
                // External is the source of truth: only bring over files it doesn't already have.
                if (file_exists($dest)) { $skipped++; continue; }
                if ($dry) { $moved++; $this->line("would copy NEW: {$rel}"); continue; }
                @mkdir(dirname($dest), 0775, true);
                if (@copy($file->getPathname(), $dest)) { $moved++; }
                else { Log::warning("ensure-external: failed copying {$rel}"); }
            }

            if ($dry) {
                $this->info("DRY-RUN: would move {$moved} new file(s) to external, skip {$skipped} already-present, then replace the folder with a symlink.");
                return self::SUCCESS;
            }

            $this->rrmdir($link);
            symlink($external, $link);
            \App\Models\ActivityLog::log('updated', "Auto-healed encounter-reports symlink (moved {$moved} new files to external, {$skipped} already present).");
            $this->info("Healed: moved {$moved} new file(s) to external, skipped {$skipped}, re-created the symlink. ✔");
            return self::SUCCESS;
        }

        // Missing entirely → just (re)create the symlink.
        if (!file_exists($link)) {
            if (!$dry) symlink($external, $link);
            $this->info('Created the missing encounter-reports symlink. ✔');
        }
        return self::SUCCESS;
    }

    private function rrmdir(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
