<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('io_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->enum('io_type', ['input', 'output']);
            $table->string('category', 50);   // oral, iv_fluid, blood, ng_tube, urine, drain, vomit, stool, other
            $table->unsignedSmallInteger('amount_ml');
            $table->string('remarks', 300)->nullable();
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            $table->index(['admission_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('io_entries');
    }
};
