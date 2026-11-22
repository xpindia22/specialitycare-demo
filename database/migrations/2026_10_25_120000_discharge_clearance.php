<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 | Discharge checklist: each department signs off before the patient leaves.
 | Beds get a "cleaning" state between discharge / transfer and the next patient.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discharge_clearances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $t->string('item', 20);                         // key of App\Services\DischargeChecklist::ITEMS
            $t->string('note', 300)->nullable();
            $t->foreignId('cleared_by')->constrained('users');
            $t->dateTime('cleared_at');
            $t->timestamps();
            $t->unique(['admission_id', 'item']);
        });

        Schema::table('admissions', function (Blueprint $t) {
            $t->string('discharge_override', 300)->nullable()->after('discharge_summary');
        });

        DB::statement("ALTER TABLE beds MODIFY status ENUM('available','occupied','reserved','maintenance','cleaning') NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        DB::table('beds')->where('status', 'cleaning')->update(['status' => 'available']);
        DB::statement("ALTER TABLE beds MODIFY status ENUM('available','occupied','reserved','maintenance') NOT NULL DEFAULT 'available'");
        Schema::table('admissions', fn (Blueprint $t) => $t->dropColumn('discharge_override'));
        Schema::dropIfExists('discharge_clearances');
    }
};
