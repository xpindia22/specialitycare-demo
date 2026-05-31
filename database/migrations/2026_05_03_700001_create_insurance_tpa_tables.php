<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master list of insurers / TPAs
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('insurer'); // insurer | tpa | govt
            $table->string('code', 50)->nullable()->unique();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            $table->string('gst_no', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insurance claims per admission
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurer_id')->constrained()->cascadeOnDelete();

            $table->string('claim_no', 80)->nullable();
            $table->string('policy_no', 80)->nullable();
            $table->string('member_id', 80)->nullable();
            $table->string('policy_holder_name')->nullable();
            $table->date('policy_expiry')->nullable();

            $table->decimal('sum_insured', 12, 2)->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('settled_amount', 12, 2)->nullable();
            $table->decimal('co_pay_amount', 12, 2)->default(0);

            $table->enum('status', [
                'draft', 'submitted', 'query_raised', 'approved', 'partially_approved',
                'settled', 'rejected', 'cancelled'
            ])->default('draft');

            $table->date('submitted_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->date('settled_date')->nullable();

            $table->text('diagnosis_icd')->nullable();
            $table->text('remarks')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['admission_id']);
            $table->index(['insurer_id', 'status']);
        });

        // Documents attached to claims
        Schema::create('insurance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->string('document_type', 80); // discharge-summary, investigation, id-proof, etc.
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_documents');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurers');
    }
};
