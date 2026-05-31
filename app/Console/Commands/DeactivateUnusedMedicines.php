<?php

namespace App\Console\Commands;

use App\Models\Medicine;
use App\Models\PharmacyStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Soft-deactivates medicines that were never prescribed AND never purchased —
 * the "seed / template" entries that clutter the master list. Sets
 * is_active=false on the medicine and on all its pharmacy_stock rows so
 * they vanish from dropdowns and search but stay in the DB (fully
 * reversible by toggling Active in Edit Medicine).
 *
 * Defense-in-depth: skips any medicine that has even ONE stock_transaction
 * row, OR any stock with quantity > 0 (in case the "never prescribed and
 * never purchased" filter missed a real holding).
 *
 * Usage:
 *   php artisan pharmacy:deactivate-unused-medicines --dry-run
 *   php artisan pharmacy:deactivate-unused-medicines
 */
class DeactivateUnusedMedicines extends Command
{
    protected $signature = 'pharmacy:deactivate-unused-medicines
                            {--dry-run : Preview the list without writing}';
    protected $description = 'Soft-deactivate medicines never prescribed and never purchased (reversible via Edit Medicine)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // Base set: never prescribed AND never purchased
        $candidates = Medicine::query()
            ->whereDoesntHave('prescriptionItems')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('pharmacy_purchase_items')
                  ->whereColumn('pharmacy_purchase_items.medicine_id', 'medicines.id');
            })
            ->where('is_active', true)
            ->get();

        $this->info($dry ? '── DRY RUN ──' : '── DEACTIVATING ──');
        $this->line("Candidates (active, never prescribed, never purchased): {$candidates->count()}");

        $stats = ['deactivated' => 0, 'skipped_has_tx' => 0, 'skipped_has_stock' => 0, 'stock_rows_off' => 0];
        DB::transaction(function () use ($candidates, $dry, &$stats) {
            foreach ($candidates as $m) {
                // Skip if any stock_transaction history (meaningful activity)
                $hasTx = DB::table('stock_transactions')->where('medicine_id', $m->id)->exists();
                if ($hasTx) { $stats['skipped_has_tx']++; continue; }

                // Skip if any active stock row has a REAL batch (not a seed pattern).
                // Seed patterns from system auto-seeders: empty/null, AUTO-NNNNN,
                // INIT-SEED-10K, TEMPLATE-STOCK, T3345. Anything else means a real
                // receipt happened — leave it alone.
                $hasRealStock = PharmacyStock::where('medicine_id', $m->id)
                    ->where('is_active', true)
                    ->whereNotNull('batch_number')
                    ->where('batch_number', '<>', '')
                    ->where('batch_number', 'not like', 'AUTO-%')
                    ->whereNotIn('batch_number', ['INIT-SEED-10K', 'TEMPLATE-STOCK', 'T3345'])
                    ->exists();
                if ($hasRealStock) { $stats['skipped_has_stock']++; continue; }

                if (!$dry) {
                    // Deactivate medicine + its (empty/seed) stock rows
                    $m->update(['is_active' => false]);
                    $offRows = PharmacyStock::where('medicine_id', $m->id)->where('is_active', true)->update(['is_active' => false]);
                    $stats['stock_rows_off'] += $offRows;
                }
                $stats['deactivated']++;
            }
        });

        $this->newLine();
        $this->info(($dry ? '[DRY RUN] ' : '') . sprintf(
            'Deactivated: %d medicines (+ %d stock rows). Skipped — has tx: %d, has positive stock: %d.',
            $stats['deactivated'], $stats['stock_rows_off'], $stats['skipped_has_tx'], $stats['skipped_has_stock']
        ));
        return self::SUCCESS;
    }
}
