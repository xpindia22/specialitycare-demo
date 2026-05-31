<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->boolean('is_mlc')->default(false)->after('status');
            $table->string('mlc_no')->nullable()->after('is_mlc');
            $table->string('mlc_police_station')->nullable()->after('mlc_no');
            $table->text('mlc_notes')->nullable()->after('mlc_police_station');
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->boolean('is_mlc')->default(false)->after('status');
            $table->string('mlc_no')->nullable()->after('is_mlc');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn(['is_mlc', 'mlc_no', 'mlc_police_station', 'mlc_notes']);
        });
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['is_mlc', 'mlc_no']);
        });
    }
};
