<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('drug1_name');
            $table->string('drug2_name');
            $table->enum('severity', ['minor', 'moderate', 'major', 'contraindicated'])->default('moderate');
            $table->text('description');
            $table->text('management')->nullable();
            $table->timestamps();
            $table->index(['drug1_name', 'drug2_name']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('drug_interactions');
    }
};
