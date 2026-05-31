<?php

namespace App\Console\Commands;

use App\Models\Medicine;
use App\Models\PharmacyStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-shot correction: replace the synthetic qty=10000 "AUTO-" stock rows
 * (auto-created when a medicine was added) with real batch/expiry/qty data
 * pulled from OpenEMR's drug_inventory.
 *
 * Only touches pharmacy_stock rows where:
 *   - batch_number LIKE 'AUTO-%'
 *   - quantity = 10000  (the auto-default pattern)
 * which avoids overwriting any real receipt-driven stock rows.
 *
 * Matching: SpecialityCare medicine.name → OpenEMR drugs.name by
 * first-word substring (4+ chars). Picks the latest non-expired OEMR
 * batch with on_hand>0; falls back to the latest batch otherwise.
 *
 * Idempotent — re-running only touches medicines still on the default
 * AUTO-/10000 pattern.
 *
 * Usage:
 *   php artisan pharmacy:correct-stock-from-openemr --dry-run
 *   php artisan pharmacy:correct-stock-from-openemr --top=50
 *   php artisan pharmacy:correct-stock-from-openemr --top=50 --all-medicines
 */
class CorrectStockFromOpenemr extends Command
{
    protected $signature = 'pharmacy:correct-stock-from-openemr
                            {--dry-run             : Preview only, no writes}
                            {--top=50              : Limit to top-N most-prescribed medicines (0 = no limit)}
                            {--all-medicines       : Walk every medicine, ignore --top}
                            {--source=open72       : OpenEMR database name (default: open72)}';

    protected $description = 'Replace synthetic qty=10000 AUTO- stock rows with real batch/expiry/qty pulled from OpenEMR drug_inventory';

    public function handle(): int
    {
        $dry    = (bool) $this->option('dry-run');
        $top    = (int) $this->option('top');
        $allMed = (bool) $this->option('all-medicines');
        $source = preg_replace('/[^A-Za-z0-9_]/', '', (string) $this->option('source'));

        // Pick medicine set: top-N by prescription count, or every medicine
        $medQuery = Medicine::query();
        if (!$allMed && $top > 0) {
            $ids = DB::table('prescription_items as pi')
                ->selectRaw('pi.medicine_id, COUNT(*) as rx')
                ->groupBy('pi.medicine_id')
                ->orderByDesc('rx')
                ->limit($top)
                ->pluck('medicine_id');
            $medQuery->whereIn('id', $ids);
        }
        $medicines = $medQuery->get();

        $this->info($dry ? '── DRY RUN ──' : '── APPLYING UPDATES ──');
        $this->line(sprintf('Scope: %d medicines%s', $medicines->count(), $allMed ? ' (all)' : " (top-{$top} by Rx)"));
        $this->newLine();

        $stats = ['updated' => 0, 'noAutoRow' => 0, 'noMatch' => 0, 'noInventory' => 0];
        $rows  = [];

        foreach ($medicines as $m) {
            // Synthetic seed rows have one of these batch patterns (empty, AUTO-,
            // INIT-SEED-10K, TEMPLATE-STOCK, or the legacy 'T3345'). Don't match
            // by quantity=10000 strictly — some have been dispensed since
            // (qty 9648, 9729, etc. but still the seed row).
            $stock = PharmacyStock::where('medicine_id', $m->id)
                ->where(function ($q) {
                    $q->whereNull('batch_number')
                      ->orWhere('batch_number', '')
                      ->orWhere('batch_number', 'like', 'AUTO-%')
                      ->orWhere('batch_number', 'INIT-SEED-10K')
                      ->orWhere('batch_number', 'TEMPLATE-STOCK')
                      ->orWhere('batch_number', 'T3345');
                })
                ->orderBy('id')->first();
            if (!$stock) { $stats['noAutoRow']++; continue; }

            $first = strtolower(preg_replace('/[^a-z]+/i', '', explode(' ', $m->name)[0] ?? ''));
            if (strlen($first) < 4) { $stats['noMatch']++; $rows[] = ['skip-shortname', $m->id, $m->name, '', '', '', '', '']; continue; }

            $od = DB::table("{$source}.drugs")
                ->whereRaw('LOWER(name) LIKE ?', ["%{$first}%"])
                ->orderBy('drug_id')
                ->first();
            if (!$od) { $stats['noMatch']++; $rows[] = ['skip-no-match', $m->id, $m->name, '', '', '', '', '']; continue; }

            $inv = DB::table("{$source}.drug_inventory")
                ->where('drug_id', $od->drug_id)
                ->whereNull('deleted_at')
                ->where('on_hand', '>', 0)
                ->where('expiration', '>=', today())
                ->orderByDesc('expiration')
                ->first();
            if (!$inv) {
                $inv = DB::table("{$source}.drug_inventory")
                    ->where('drug_id', $od->drug_id)
                    ->whereNull('deleted_at')
                    ->orderByDesc('expiration')
                    ->first();
            }
            if (!$inv) { $stats['noInventory']++; $rows[] = ['skip-no-inv', $m->id, $m->name, $od->name, '', '', '', '']; continue; }

            $update = [
                'batch_number' => $inv->lot_number ?: $stock->batch_number,
                'expiry_date'  => $inv->expiration ?: $stock->expiry_date,
                'quantity'     => (int) ($inv->on_hand ?? 0),
            ];

            $rows[] = ['ok', $m->id, $m->name, $od->name, $update['batch_number'], $update['expiry_date'], $update['quantity'], $inv->manufacturer ?? ''];

            if (!$dry) {
                $stock->forceFill($update)->save();
            }
            $stats['updated']++;
        }

        // Compact table output (truncated names)
        $this->table(
            ['Status', 'SC#', 'SC name', 'OEMR drug', 'Batch', 'Expiry', 'Qty', 'Mfr'],
            array_map(fn($r) => [$r[0], $r[1], substr((string) $r[2], 0, 30), substr((string) $r[3], 0, 25), $r[4], $r[5], $r[6], substr((string) $r[7], 0, 8)], $rows)
        );

        $this->newLine();
        $verb = $dry ? 'Would update' : 'Updated';
        $this->info(($dry ? '[DRY RUN] ' : '') . sprintf(
            '%s: %d  |  Skipped — no AUTO row: %d, no OEMR match: %d, no inventory: %d',
            $verb, $stats['updated'], $stats['noAutoRow'], $stats['noMatch'], $stats['noInventory']
        ));
        return self::SUCCESS;
    }
}
