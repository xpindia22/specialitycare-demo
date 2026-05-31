<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('encounter_templates', function (Blueprint $table) {
            // JSON: { "tests": ["TestName1", ...], "panels": ["PanelName1", ...] }
            $table->json('lab_orders')->nullable()->after('prescription_template');
        });
    }

    public function down(): void
    {
        Schema::table('encounter_templates', function (Blueprint $table) {
            $table->dropColumn('lab_orders');
        });
    }
};
