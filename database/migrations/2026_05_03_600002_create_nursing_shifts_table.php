<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nursing_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->date('shift_date');
            $table->enum('shift', ['morning', 'evening', 'night']);   // 06:00-14:00, 14:00-22:00, 22:00-06:00
            $table->string('patient_condition', 100)->nullable();      // stable, critical, improving, deteriorating
            $table->text('assessment')->nullable();                    // brief nursing assessment
            $table->text('care_given')->nullable();                    // procedures / care done this shift
            $table->text('medications_given')->nullable();             // drug summary
            $table->text('iv_lines')->nullable();                      // IV sites, patency
            $table->text('pending_tasks')->nullable();                 // what to hand over
            $table->text('special_instructions')->nullable();
            $table->foreignId('written_by')->constrained('users');
            $table->timestamps();

            $table->unique(['admission_id', 'shift_date', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_shifts');
    }
};
