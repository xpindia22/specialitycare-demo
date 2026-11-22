<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | OPD fee rules: consultation fee by department / doctor, follow-up and free
 | follow-up windows, and patient discounts (automatic by age, or picked at billing).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_fee_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('doctor_id')->nullable()->constrained()->cascadeOnDelete();
            $t->decimal('new_fee', 10, 2)->nullable();          // null = doctor's consultation fee
            $t->decimal('followup_fee', 10, 2)->nullable();     // null = "Follow-up Consultation" service price
            $t->unsignedSmallInteger('followup_days')->nullable();
            $t->unsignedSmallInteger('free_days')->nullable();
            $t->unsignedTinyInteger('free_visits')->default(1);
            $t->string('notes', 255)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['department_id', 'doctor_id']);
        });

        Schema::create('opd_discounts', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80);
            $t->decimal('percent', 5, 2);
            $t->unsignedTinyInteger('age_min')->nullable();     // age_min / age_max set = applied automatically
            $t->unsignedTinyInteger('age_max')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_discounts');
        Schema::dropIfExists('opd_fee_rules');
    }
};
