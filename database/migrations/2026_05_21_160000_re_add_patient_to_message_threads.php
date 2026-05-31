<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Re-add message_threads.patient_id.
 *
 * The 2026_05_20_150000 migration first added this column, then
 * 2026_05_21_090000 dropped it when patient↔conversation links moved to the
 * multi-patient `patient_communications` table. We now bring the column back
 * — but for a different role: it marks the SINGLE patient a DM thread is
 * "about", giving each patient a dedicated thread per colleague (see
 * MessageController::storeCompose). The `patient_communications` rows still
 * carry the additive multi-patient communication log; this column is purely
 * the thread's canonical identity. Guarded so it's a no-op where the column
 * already exists (e.g. installs where 090000's drop hasn't run).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('message_threads', 'patient_id')) {
            Schema::table('message_threads', function (Blueprint $t) {
                $t->foreignId('patient_id')->nullable()->after('subject')
                    ->constrained('patients')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('message_threads', 'patient_id')) {
            Schema::table('message_threads', function (Blueprint $t) {
                $t->dropConstrainedForeignId('patient_id');
            });
        }
    }
};
