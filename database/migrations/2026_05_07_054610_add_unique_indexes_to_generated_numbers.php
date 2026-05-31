<?php

use Illuminate\Database\Migrations\Migration;

// Verified 2026-05-07: ipd_no, bill_no, appointment_no, order_no all already
// carry unique indexes from their original create-table migrations.
// No schema changes needed.
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
