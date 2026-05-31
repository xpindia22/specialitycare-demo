<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate legacy 'New' values before changing the ENUM
        DB::statement("UPDATE appointments SET type='New Consultation' WHERE type='New'");
        DB::statement("ALTER TABLE appointments MODIFY COLUMN type ENUM('New Consultation','Follow-up','Review','Emergency','Teleconsult') NOT NULL DEFAULT 'New Consultation'");
    }

    public function down(): void
    {
        DB::statement("UPDATE appointments SET type='New' WHERE type='New Consultation'");
        DB::statement("ALTER TABLE appointments MODIFY COLUMN type ENUM('New','Follow-up','Emergency','Teleconsult') NOT NULL DEFAULT 'New'");
    }
};
