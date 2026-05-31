<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // false = staff record only, no login. Used for cleaners, drivers,
            // ward boys etc. who need to be tracked in HR/attendance but never
            // log in. is_active is also kept false on these users as a belt-
            // and-braces guard against accidental login.
            $table->boolean('has_system_access')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_system_access');
        });
    }
};
