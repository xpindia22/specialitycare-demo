<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('triage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_token_id')->constrained('opd_tokens')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->enum('triage_level', ['green','yellow','red'])->default('green');
            $table->string('chief_complaint')->nullable();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->decimal('spo2', 4, 1)->nullable();
            $table->decimal('weight', 5, 1)->nullable();
            $table->decimal('height', 5, 1)->nullable();
            $table->decimal('bmi', 4, 1)->nullable();
            $table->string('blood_glucose')->nullable();
            $table->text('nurse_notes')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Track triage status on the token
        Schema::table('opd_tokens', function (Blueprint $table) {
            $table->boolean('is_triaged')->default(false)->after('notes');
        });
    }

    public function down(): void {
        Schema::table('opd_tokens', fn($t) => $t->dropColumn('is_triaged'));
        Schema::dropIfExists('triage_records');
    }
};
