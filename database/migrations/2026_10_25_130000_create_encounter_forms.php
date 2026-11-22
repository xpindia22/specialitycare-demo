<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Speciality visit forms (eye, gynae, ortho, skin …) filled on an OPD encounter.
 | Fields are defined in config/opd_forms.php and stored as JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_forms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->string('form', 20);                         // department code, key of config('opd_forms')
            $t->json('data');
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['encounter_id', 'form']);
            $t->index(['patient_id', 'form']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_forms');
    }
};
