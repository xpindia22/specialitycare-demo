<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One ongoing conversation per patient with the clinic (shared staff inbox).
 * Patients aren't Users, so this lives outside the staff message_threads.
 * staff_read_at is shared across all staff (any staff reading marks it read).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_conversations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();
            $t->timestamp('last_message_at')->nullable();
            $t->timestamp('patient_read_at')->nullable();
            $t->timestamp('staff_read_at')->nullable();
            $t->timestamps();
            $t->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_conversations');
    }
};
