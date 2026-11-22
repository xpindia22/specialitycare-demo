<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glucose_logs', function (Blueprint $table) {
            $table->string('medications', 500)->nullable()->change();
            $table->string('symptoms', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('glucose_logs', function (Blueprint $table) {
            $table->string('medications', 255)->nullable()->change();
            $table->string('symptoms', 255)->nullable()->change();
        });
    }
};
