<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consent_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);                 // e.g. "Anaesthesia Consent — General"
            $table->string('category', 80)->nullable();  // surgical / anaesthesia / blood / procedure / clinical_trial
            $table->text('body_html');                   // rich text — supports {patient_name}, {procedure}, {date} etc.
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('consent_templates'); }
};
