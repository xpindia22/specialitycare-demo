<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('booking_source', 30)->default('staff')->after('status');
            $table->string('public_name', 120)->nullable()->after('booking_source');
            $table->string('public_phone', 20)->nullable()->after('public_name');
            // Make created_by nullable so public bookings (no auth user) can be stored
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });
    }

    public function down(): void {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['booking_source', 'public_name', 'public_phone']);
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });
    }
};
