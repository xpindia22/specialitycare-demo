<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('opd_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('token_no');
            $table->date('token_date');
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->enum('status', ['waiting','called','in_consultation','done','skipped'])->default('waiting');
            $table->enum('priority', ['normal','urgent'])->default('normal');
            $table->string('purpose')->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('consultation_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['token_no','token_date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('opd_tokens');
    }
};
