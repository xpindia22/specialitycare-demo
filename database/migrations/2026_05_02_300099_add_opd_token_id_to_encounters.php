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
        if (!Schema::hasColumn('encounters', 'opd_token_id')) {
            Schema::table('encounters', function (Blueprint $table) {
                $table->unsignedBigInteger('opd_token_id')->nullable()->after('appointment_id');
            });
        }

        $fks = collect(\DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encounters' AND COLUMN_NAME = 'opd_token_id' AND REFERENCED_TABLE_NAME = 'opd_tokens'"));
        if ($fks->isEmpty()) {
            Schema::table('encounters', function (Blueprint $table) {
                $table->foreign('opd_token_id')->references('id')->on('opd_tokens')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\OpdToken::class);
        });
    }
};
