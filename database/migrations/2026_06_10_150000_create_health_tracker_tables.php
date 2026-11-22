<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Simple numeric time-series: weight, heart_rate, spo2, stress, …
        Schema::create('health_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('logged_at')->index();
            $table->string('metric', 20);                 // weight | heart_rate | spo2 | stress
            $table->decimal('value', 7, 2);
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['patient_id', 'metric', 'logged_at']);
        });

        // Food intake with calorie value.
        Schema::create('diet_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('logged_at')->index();
            $table->string('meal', 20)->nullable();        // breakfast | lunch | dinner | snack
            $table->string('food', 191);
            $table->string('quantity', 60)->nullable();
            $table->unsignedInteger('calories')->nullable();
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['patient_id', 'logged_at']);
        });

        // Exercise with duration + calories burned.
        Schema::create('exercise_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('logged_at')->index();
            $table->string('activity', 100);
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->unsignedInteger('calories_burned')->nullable();
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['patient_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_logs');
        Schema::dropIfExists('diet_logs');
        Schema::dropIfExists('health_metrics');
    }
};
