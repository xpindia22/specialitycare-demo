<?php

namespace App\Console\Commands;

use App\Models\Medicine;
use App\Services\MedicineHsnClassifier;
use Illuminate\Console\Command;

/**
 * Walks every medicine and applies the heuristic classifier. Use --only-default
 * to refresh only the ones still at the catch-all default (typical first run).
 * Use --force to overwrite anything the user has already set (use with care).
 */
class ClassifyMedicineHsn extends Command
{
    protected $signature = 'pharmacy:classify-medicines
                            {--dry-run      : Show what would change without writing}
                            {--only-default : Only touch medicines still at the 0%/default HSN}
                            {--force        : Overwrite even when the medicine already has non-default HSN/GST}';

    protected $description = 'Auto-classify medicines into proper HSN + GST rate using category and name heuristics';

    public function handle(MedicineHsnClassifier $classifier): int
    {
        $dry  = $this->option('dry-run');
        $only = $this->option('only-default');
        $force = $this->option('force');

        $defaultGstId = \App\Models\GstRate::where('rate', 0)->value('id');

        $q = Medicine::with('category', 'gstRate');
        if ($only && !$force) {
            $q->where(fn($x) => $x
                ->where('hsn_code', '30049099')
                ->orWhereNull('hsn_code')
                ->orWhere('hsn_code', ''))
              ->where(fn($x) => $x
                ->where('gst_rate_id', $defaultGstId)
                ->orWhereNull('gst_rate_id'));
        }

        $touched = $unchanged = 0;
        $byRate = $byHsn = [];

        foreach ($q->cursor() as $m) {
            $new = $classifier->classify($m);
            $changed = ($m->hsn_code !== $new['hsn_code']) || ((int) $m->gst_rate_id !== (int) $new['gst_rate_id']);
            if (!$changed) { $unchanged++; continue; }

            $newRateLabel = \App\Models\GstRate::find($new['gst_rate_id'])?->rate . '%';
            $byRate[$newRateLabel] = ($byRate[$newRateLabel] ?? 0) + 1;
            $byHsn[$new['hsn_code']] = ($byHsn[$new['hsn_code']] ?? 0) + 1;

            if (!$dry) {
                $m->forceFill($new)->save();
            }
            $touched++;
        }

        $this->newLine();
        $this->info(($dry ? '[DRY RUN] ' : '') . "Classified — touched: {$touched}, unchanged: {$unchanged}");
        $this->line('GST breakdown:');
        foreach ($byRate as $rate => $cnt) $this->line("  → GST {$rate}: {$cnt} medicines");
        $this->line('HSN breakdown:');
        ksort($byHsn);
        foreach ($byHsn as $hsn => $cnt) $this->line("  → HSN {$hsn}: {$cnt} medicines");

        return self::SUCCESS;
    }
}
