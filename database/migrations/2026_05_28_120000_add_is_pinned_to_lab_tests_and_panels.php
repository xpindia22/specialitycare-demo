<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lab_tests', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_active');
        });
        Schema::table('lab_panels', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('lab_tests', fn (Blueprint $t) => $t->dropColumn('is_pinned'));
        Schema::table('lab_panels', fn (Blueprint $t) => $t->dropColumn('is_pinned'));
    }
};
