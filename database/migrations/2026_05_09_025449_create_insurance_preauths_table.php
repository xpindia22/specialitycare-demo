<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_preauths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->string('insurer_name');
            $table->string('auth_number')->nullable();
            $table->date('requested_at');
            $table->date('approved_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'partial'])->default('pending');
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_preauths');
    }
};
