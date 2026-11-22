<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->string('mva')->nullable()->after('mitral_valve');
            $table->string('mr_vena_contracta')->nullable()->after('mva');
            $table->string('mr_regurgitant_vol')->nullable()->after('mr_vena_contracta');
        });
    }

    public function down(): void {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->dropColumn(['mva', 'mr_vena_contracta', 'mr_regurgitant_vol']);
        });
    }
};
