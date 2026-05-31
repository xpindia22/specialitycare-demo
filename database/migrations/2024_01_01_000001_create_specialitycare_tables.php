<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('staff')->after('password');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('staff_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->date('date_of_birth')->nullable();
            $table->string('phone');
            $table->string('alt_phone')->nullable();
            $table->string('email');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('designation');
            $table->string('department')->nullable();
            $table->date('joining_date');
            $table->string('qualification')->nullable();
            $table->string('photo')->nullable();
            $table->string('aadhar_no')->nullable();
            $table->string('pan_no')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('doctor_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->date('date_of_birth')->nullable();
            $table->string('phone');
            $table->string('alt_phone')->nullable();
            $table->string('email');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('specialization');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->string('qualification');
            $table->string('registration_no');
            $table->integer('experience_years')->default(0);
            $table->string('photo')->nullable();
            $table->string('signature')->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->text('bio')->nullable();
            $table->json('available_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('slot_duration')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('jhc_id')->unique();
            $table->string('openemr_pid')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other', 'Unknown']);
            $table->date('date_of_birth');
            $table->string('blood_group')->nullable();
            $table->string('phone');
            $table->string('alt_phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('country')->default('India');
            $table->string('aadhar_no')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->string('occupation')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('religion')->nullable();
            $table->string('nationality')->default('Indian');
            $table->string('referred_by')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('jhc_id');
            $table->index('phone');
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_no')->unique();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->integer('duration')->default(15);
            $table->enum('type', ['New', 'Follow-up', 'Emergency', 'Teleconsult'])->default('New');
            $table->enum('status', ['Scheduled', 'Confirmed', 'Checked-In', 'In-Progress', 'Completed', 'Cancelled', 'No-Show', 'Rescheduled'])->default('Scheduled');
            $table->text('chief_complaint')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('rescheduled_from')->nullable()->constrained('appointments');
            $table->string('reschedule_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['appointment_date', 'doctor_id']);
        });

        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->string('encounter_no')->unique();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments');
            $table->date('encounter_date');
            $table->time('encounter_time');
            $table->enum('visit_type', ['New Visit', 'Revisit', 'Emergency', 'Follow-up', 'Review'])->default('New Visit');
            $table->enum('status', ['Open', 'Completed', 'Cancelled'])->default('Open');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('bmi', 4, 2)->nullable();
            $table->string('bp_systolic')->nullable();
            $table->string('bp_diastolic')->nullable();
            $table->decimal('pulse', 5, 1)->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->decimal('spo2', 4, 1)->nullable();
            $table->decimal('respiratory_rate', 4, 1)->nullable();
            $table->string('blood_glucose')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->text('past_medical_history')->nullable();
            $table->text('family_history')->nullable();
            $table->text('social_history')->nullable();
            $table->text('allergies')->nullable();
            $table->text('review_of_systems')->nullable();
            $table->text('examination_findings')->nullable();
            $table->text('impression')->nullable();
            $table->text('plan')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->integer('follow_up_days')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained('encounters')->onDelete('cascade');
            $table->string('icd_code')->nullable();
            $table->string('diagnosis_name');
            $table->enum('type', ['Primary', 'Secondary', 'Differential'])->default('Primary');
            $table->enum('status', ['Active', 'Resolved', 'Chronic'])->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->string('note_type')->default('Progress Note');
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->longText('note_content')->nullable();
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('medicine_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->foreignId('medicine_category_id')->nullable()->constrained('medicine_categories');
            $table->string('dosage_form');
            $table->string('strength')->nullable();
            $table->string('unit')->default('Tab');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('reorder_level')->default(10);
            $table->text('indications')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('drug_interactions')->nullable();
            $table->text('dosage_instructions')->nullable();
            $table->string('manufacturer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pharmacy_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('min_stock_level')->default(10);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines');
            $table->foreignId('pharmacy_stock_id')->nullable()->constrained('pharmacy_stock');
            $table->foreignId('prescription_id')->nullable();
            $table->string('type');
            $table->integer('quantity');
            $table->integer('balance_after')->default(0);
            $table->string('batch_number')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_no')->unique();
            $table->foreignId('encounter_id')->constrained('encounters')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->date('prescribed_date');
            $table->text('notes')->nullable();
            $table->text('advice')->nullable();
            $table->enum('status', ['Pending', 'Dispensed', 'Partially Dispensed', 'Cancelled'])->default('Pending');
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines');
            $table->string('dose');
            $table->string('frequency');
            $table->string('route')->default('Oral');
            $table->integer('duration_days');
            $table->integer('quantity');
            $table->text('instructions')->nullable();
            $table->boolean('is_dispensed')->default(false);
            $table->timestamps();
        });

        Schema::create('lab_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->timestamps();
        });

        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_category_id')->constrained('lab_categories');
            $table->string('name');
            $table->string('short_code')->nullable();
            $table->text('description')->nullable();
            $table->string('sample_type')->nullable();
            $table->string('normal_range')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('turnaround_hours')->nullable();
            $table->string('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lab_panels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('turnaround_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lab_panel_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_panel_id')->constrained('lab_panels')->onDelete('cascade');
            $table->foreignId('lab_test_id')->constrained('lab_tests')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->date('ordered_date');
            $table->enum('status', ['Ordered', 'Collected', 'Processing', 'Completed', 'Cancelled'])->default('Ordered');
            $table->text('clinical_info')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('lab_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->onDelete('cascade');
            $table->foreignId('lab_test_id')->nullable()->constrained('lab_tests');
            $table->foreignId('lab_panel_id')->nullable()->constrained('lab_panels');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['Ordered', 'Collected', 'Processing', 'Completed'])->default('Ordered');
            $table->timestamps();
        });

        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_item_id')->constrained('lab_order_items')->onDelete('cascade');
            $table->foreignId('lab_test_id')->constrained('lab_tests');
            $table->string('parameter_name');
            $table->string('result_value');
            $table->string('unit')->nullable();
            $table->string('normal_range')->nullable();
            $table->enum('flag', ['Normal', 'High', 'Low', 'Critical High', 'Critical Low'])->default('Normal');
            $table->text('comments')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users');
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ecg_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_no')->unique();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->date('record_date');
            $table->string('heart_rate')->nullable();
            $table->string('rhythm')->nullable();
            $table->string('pr_interval')->nullable();
            $table->string('qrs_duration')->nullable();
            $table->string('qt_qtc')->nullable();
            $table->string('axis')->nullable();
            $table->text('interpretation')->nullable();
            $table->string('ecg_file')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('performed_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('echo_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_no')->unique();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->date('record_date');
            $table->string('lv_edd')->nullable();
            $table->string('lv_esd')->nullable();
            $table->string('ef_percent')->nullable();
            $table->string('lvpw')->nullable();
            $table->string('ivs')->nullable();
            $table->string('la_size')->nullable();
            $table->string('ao_root')->nullable();
            $table->string('rv_dimension')->nullable();
            $table->text('mitral_valve')->nullable();
            $table->text('aortic_valve')->nullable();
            $table->text('tricuspid_valve')->nullable();
            $table->text('pulmonary_valve')->nullable();
            $table->text('impression')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('echo_file')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('performed_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('service_charges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('price', 10, 2);
            $table->boolean('is_taxable')->default(false);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no')->unique();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters');
            $table->date('bill_date');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2)->default(0);
            $table->enum('status', ['Unpaid', 'Partial', 'Paid', 'Refunded'])->default('Unpaid');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index('bill_date');
        });

        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->onDelete('cascade');
            $table->string('item_type');
            $table->string('item_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('transaction_ref')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('icd_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description');
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icd_codes');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('service_charges');
        Schema::dropIfExists('echo_records');
        Schema::dropIfExists('ecg_records');
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_panel_tests');
        Schema::dropIfExists('lab_panels');
        Schema::dropIfExists('lab_tests');
        Schema::dropIfExists('lab_categories');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('pharmacy_stock');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('medicine_categories');
        Schema::dropIfExists('clinical_notes');
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('encounters');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('departments');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
