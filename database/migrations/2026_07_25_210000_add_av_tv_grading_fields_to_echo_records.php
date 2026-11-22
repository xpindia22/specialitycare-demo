<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->string('ava')->nullable()->after('aortic_valve');
            $table->string('ar_vena_contracta')->nullable()->after('ava');
            $table->string('ar_regurgitant_vol')->nullable()->after('ar_vena_contracta');
            $table->string('tr_vena_contracta')->nullable()->after('tricuspid_valve');
            $table->string('tr_regurgitant_vol')->nullable()->after('tr_vena_contracta');
        });
    }

    public function down(): void {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->dropColumn(['ava', 'ar_vena_contracta', 'ar_regurgitant_vol', 'tr_vena_contracta', 'tr_regurgitant_vol']);
        });
    }
};
