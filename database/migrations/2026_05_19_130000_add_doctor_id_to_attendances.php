<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Either staff_id or doctor_id is set on each row; never both, never neither.
            $table->foreignId('staff_id')->nullable()->change();
            $table->foreignId('doctor_id')->nullable()->after('staff_id')
                ->constrained('doctors')->cascadeOnDelete();
            $table->unique(['doctor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['doctor_id', 'date']);
            $table->dropConstrainedForeignId('doctor_id');
            $table->foreignId('staff_id')->nullable(false)->change();
        });
    }
};
