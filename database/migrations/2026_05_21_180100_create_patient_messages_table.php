<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messages in a patient↔clinic conversation. sender = 'patient' or 'staff';
 * user_id is the staff member who sent a 'staff' message (null for patient
 * messages). Distinct from message_attachments etc. used by staff DMs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_conversation_id')->constrained('patient_conversations')->cascadeOnDelete();
            $t->string('sender', 10);                       // 'patient' | 'staff'
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('body');
            $t->timestamps();
            $t->index(['patient_conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_messages');
    }
};
