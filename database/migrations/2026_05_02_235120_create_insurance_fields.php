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
        Schema::table('patients', function (Blueprint $table) {
            $table->string('insurance_company')->nullable()->after('referred_by');
            $table->string('policy_no')->nullable()->after('insurance_company');
            $table->string('tpa_name')->nullable()->after('policy_no');
            $table->date('insurance_valid_until')->nullable()->after('tpa_name');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('insurance_amount', 10, 2)->default(0)->after('discount_amount');
            $table->string('insurance_company')->nullable()->after('insurance_amount');
            $table->string('tpa_ref')->nullable()->after('insurance_company');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['insurance_company', 'policy_no', 'tpa_name', 'insurance_valid_until']);
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['insurance_amount', 'insurance_company', 'tpa_ref']);
        });
    }
};
