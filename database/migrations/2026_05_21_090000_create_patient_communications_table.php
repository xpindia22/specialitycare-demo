<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_communications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            // whatsapp | email | call | dm | in_person
            $t->string('mode', 20)->default('dm');
            // The doctor/staff the discussion was with (other party).
            $t->foreignId('with_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Only set for mode=dm — links to the in-app conversation.
            $t->foreignId('message_thread_id')->nullable()->constrained('message_threads')->cascadeOnDelete();
            $t->text('note')->nullable();
            $t->timestamp('occurred_at')->nullable();   // when the discussion happened / when linked
            $t->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['patient_id', 'occurred_at']);
            $t->index('message_thread_id');
        });

        // Migrate existing single thread→patient links into dm-mode rows, then
        // retire the one-patient column (a thread can now link many patients).
        if (Schema::hasColumn('message_threads', 'patient_id')) {
            foreach (DB::table('message_threads')->whereNotNull('patient_id')->get() as $th) {
                DB::table('patient_communications')->insert([
                    'patient_id'        => $th->patient_id,
                    'mode'              => 'dm',
                    'message_thread_id' => $th->id,
                    'occurred_at'       => $th->last_message_at ?? $th->created_at ?? now(),
                    'logged_by'         => $th->created_by,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
            Schema::table('message_threads', function (Blueprint $t) {
                $t->dropConstrainedForeignId('patient_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('message_threads', function (Blueprint $t) {
            $t->foreignId('patient_id')->nullable()->after('subject')->constrained('patients')->nullOnDelete();
        });
        Schema::dropIfExists('patient_communications');
    }
};
