<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Convert enum → varchar so we can accept 'custom' (and any future values)
        // without locking ourselves into ALTER TABLE … MODIFY ENUM dance again.
        DB::statement("ALTER TABLE duty_rosters MODIFY shift VARCHAR(20) NOT NULL DEFAULT 'morning'");

        Schema::table('duty_rosters', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('shift');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down(): void {
        Schema::table('duty_rosters', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
        DB::statement("ALTER TABLE duty_rosters MODIFY shift ENUM('morning','evening','night') NOT NULL DEFAULT 'morning'");
    }
};
