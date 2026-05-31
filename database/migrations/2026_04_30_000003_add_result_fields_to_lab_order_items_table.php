<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_order_items', 'result_value')) {
                $table->string('result_value')->nullable()->after('status');
            }

            if (!Schema::hasColumn('lab_order_items', 'result_status')) {
                $table->string('result_status')->default('Pending')->after('result_value');
            }

            if (!Schema::hasColumn('lab_order_items', 'abnormal_flag')) {
                $table->string('abnormal_flag', 2)->nullable()->after('result_status');
            }

            if (!Schema::hasColumn('lab_order_items', 'remarks')) {
                $table->text('remarks')->nullable()->after('abnormal_flag');
            }

            if (!Schema::hasColumn('lab_order_items', 'result_entered_at')) {
                $table->timestamp('result_entered_at')->nullable()->after('remarks');
            }

            if (!Schema::hasColumn('lab_order_items', 'result_entered_by')) {
                $table->foreignId('result_entered_by')
                    ->nullable()
                    ->after('result_entered_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('lab_order_items', 'result_entered_by')) {
                $table->dropConstrainedForeignId('result_entered_by');
            }

            foreach (['result_entered_at', 'remarks', 'abnormal_flag', 'result_status', 'result_value'] as $column) {
                if (Schema::hasColumn('lab_order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

