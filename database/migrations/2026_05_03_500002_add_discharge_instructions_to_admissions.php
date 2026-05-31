<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('admissions', function (Blueprint $table) {
            $table->text('discharge_instructions')->nullable()->after('discharge_summary');
        });
    }

    public function down(): void {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn('discharge_instructions');
        });
    }
};
