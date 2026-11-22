<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE lab_order_items MODIFY abnormal_flag VARCHAR(20) NULL");
        DB::statement("ALTER TABLE lab_results MODIFY flag VARCHAR(30) NOT NULL DEFAULT 'Normal'");
    }

    public function down(): void
    {
        DB::statement("UPDATE lab_order_items SET abnormal_flag = NULL WHERE abnormal_flag NOT IN ('H', 'L', 'HH', 'LL')");
        DB::statement("UPDATE lab_results SET flag = 'Normal' WHERE flag NOT IN ('Normal', 'High', 'Low', 'Critical High', 'Critical Low')");
        DB::statement("ALTER TABLE lab_order_items MODIFY abnormal_flag VARCHAR(2) NULL");
        DB::statement("ALTER TABLE lab_results MODIFY flag ENUM('Normal','High','Low','Critical High','Critical Low') NOT NULL DEFAULT 'Normal'");
    }
};

