<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // When ON, the system auto-creates a Present attendance record
            // for this staff on every date from joining_date forward that
            // has no manual entry. Manual edits are never overwritten.
            $table->boolean('auto_present')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('auto_present');
        });
    }
};
