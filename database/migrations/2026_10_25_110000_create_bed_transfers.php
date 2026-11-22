<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 | Ward / bed transfer history. Earlier transfers were only written as a
 | nursing note ("Ward transfer: NM1 Bed 3 → ICU Bed 2. Reason: …"); those
 | notes are read back into the table where the ward and bed can be matched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bed_transfers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $t->foreignId('from_ward_id')->constrained('wards');
            $t->foreignId('from_bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $t->foreignId('to_ward_id')->constrained('wards');
            $t->foreignId('to_bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $t->dateTime('transferred_at');
            $t->string('reason', 300)->nullable();
            $t->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['admission_id', 'transferred_at']);
        });

        $notes = DB::table('ipd_notes')->where('note', 'like', 'Ward transfer:%')->orderBy('noted_at')->get();
        foreach ($notes as $n) {
            if (!preg_match('/^Ward transfer: (.+) Bed (\S+) → (.+) Bed (\S+?)(?:\. Reason: (.*))?$/u', $n->note, $m)) continue;
            $fromWard = DB::table('wards')->where('name', $m[1])->value('id');
            $toWard = DB::table('wards')->where('name', $m[3])->value('id');
            if (!$fromWard || !$toWard) continue;
            DB::table('bed_transfers')->insert([
                'admission_id'   => $n->admission_id,
                'from_ward_id'   => $fromWard,
                'from_bed_id'    => DB::table('beds')->where('ward_id', $fromWard)->where('bed_number', $m[2])->value('id'),
                'to_ward_id'     => $toWard,
                'to_bed_id'      => DB::table('beds')->where('ward_id', $toWard)->where('bed_number', $m[4])->value('id'),
                'transferred_at' => $n->noted_at,
                'reason'         => $m[5] ?? null,
                'transferred_by' => $n->written_by ?? null,
                'created_at'     => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_transfers');
    }
};
