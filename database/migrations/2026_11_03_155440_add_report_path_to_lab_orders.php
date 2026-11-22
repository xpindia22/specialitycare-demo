<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('lab_orders', function (Blueprint $t) {
            if (!Schema::hasColumn('lab_orders', 'report_path')) {
                $t->string('report_path')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('lab_orders', 'source')) {
                $t->string('source', 20)->nullable()->after('report_path'); // manual|upload
            }
        });
    }
    public function down(): void {
        Schema::table('lab_orders', function (Blueprint $t) {
            foreach (['report_path','source'] as $c) {
                if (Schema::hasColumn('lab_orders', $c)) $t->dropColumn($c);
            }
        });
    }
};
