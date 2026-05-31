<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('patients', function (Blueprint $table) {
            $table->text('allergies')->nullable()->after('photo');
            $table->string('photo_path')->nullable()->after('photo');
        });
    }

    public function down(): void {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['allergies', 'photo_path']);
        });
    }
};
