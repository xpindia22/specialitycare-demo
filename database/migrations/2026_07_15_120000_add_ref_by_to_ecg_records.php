<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecg_records', function (Blueprint $table) {
            $table->string('ref_by')->nullable()->after('record_date');
        });
    }

    public function down(): void
    {
        Schema::table('ecg_records', function (Blueprint $table) {
            $table->dropColumn('ref_by');
        });
    }
};
