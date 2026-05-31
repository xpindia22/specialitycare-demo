<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['general','semi_private','private','icu','hdu','emergency'])->default('general');
            $table->string('floor')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained('wards')->cascadeOnDelete();
            $table->string('bed_number');
            $table->enum('type', ['general','semi_private','private','icu','hdu'])->default('general');
            $table->enum('status', ['available','occupied','reserved','maintenance'])->default('available');
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['ward_id','bed_number']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
    }
};
