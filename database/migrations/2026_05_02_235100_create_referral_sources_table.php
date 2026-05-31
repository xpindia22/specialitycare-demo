<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['doctor', 'hospital', 'clinic', 'other'])->default('doctor');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('rate_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('rate_value', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_sources');
    }
};
