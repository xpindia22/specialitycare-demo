<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounter_reports', function (Blueprint $table) {
            $table->string('source_system')->nullable()->after('uploaded_by');
            $table->string('source_id')->nullable()->after('source_system');
            $table->text('source_url')->nullable()->after('source_id');
            $table->unique(['source_system', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('encounter_reports', function (Blueprint $table) {
            $table->dropUnique(['source_system', 'source_id']);
            $table->dropColumn(['source_system', 'source_id', 'source_url']);
        });
    }
};
