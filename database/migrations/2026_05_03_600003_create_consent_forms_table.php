<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consent_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->enum('consent_type', ['admission', 'surgical', 'anaesthesia', 'procedure', 'blood_transfusion']);
            $table->string('procedure_name', 200)->nullable();   // for surgical/procedure types
            $table->boolean('is_obtained')->default(false);
            $table->string('patient_or_guardian', 150)->nullable();
            $table->string('relationship', 80)->nullable();      // self, spouse, parent, etc.
            $table->string('witness_name', 150)->nullable();
            $table->timestamp('obtained_at')->nullable();
            $table->foreignId('obtained_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['admission_id', 'consent_type', 'procedure_name'], 'consent_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_forms');
    }
};
