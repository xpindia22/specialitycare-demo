<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('to_phone', 32);                  // E.164, e.g. +917432001215
            $table->string('kind', 40)->default('manual');   // appointment_confirm, appointment_reminder, followup, vaccination, billing, manual, test
            $table->text('body');
            $table->string('twilio_sid', 64)->nullable();    // SMxxxxxxxx returned by Twilio
            $table->string('status', 24)->default('queued'); // queued|sent|failed
            $table->text('error')->nullable();               // Twilio error message
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'kind']);
            $table->index(['kind', 'sent_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('whatsapp_messages'); }
};
