<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_no', 20)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('certificate_type', [
                'medical_leave',
                'fitness',
                'accident',
                'medical_examination',
                'age_nativity',
                'attendant',
                'domiciliary',
            ]);
            $table->date('issued_date');
            $table->string('purpose', 255)->nullable();
            $table->string('diagnosis', 255)->nullable();
            $table->date('rest_from')->nullable();
            $table->date('rest_to')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
