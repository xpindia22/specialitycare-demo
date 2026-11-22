<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->string('la_clot')->nullable()->after('rwma_comments');
            $table->string('lv_clot')->nullable()->after('la_clot');
        });
    }

    public function down(): void {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->dropColumn(['la_clot', 'lv_clot']);
        });
    }
};
