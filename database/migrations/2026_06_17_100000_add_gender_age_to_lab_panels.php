<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_panels', function (Blueprint $table) {
            $table->string('gender', 10)->default('all')->after('description'); // all | male | female
            $table->unsignedSmallInteger('min_age')->nullable()->after('gender');
            $table->unsignedSmallInteger('max_age')->nullable()->after('min_age');
        });
    }

    public function down(): void
    {
        Schema::table('lab_panels', function (Blueprint $table) {
            $table->dropColumn(['gender', 'min_age', 'max_age']);
        });
    }
};
