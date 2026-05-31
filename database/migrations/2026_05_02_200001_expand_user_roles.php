<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Rename generic 'staff' to 'receptionist' as the default non-clinical staff role
        DB::table('users')->where('role', 'staff')->update(['role' => 'receptionist']);
    }
    public function down(): void {
        DB::table('users')->where('role', 'receptionist')->update(['role' => 'staff']);
    }
};
