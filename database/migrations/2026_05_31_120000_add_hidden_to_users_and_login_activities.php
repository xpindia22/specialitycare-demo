<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'hidden')) {
            Schema::table('users', function (Blueprint $table) {
                // Hidden owner/admin accounts: full access, but excluded from
                // Users / Doctors / Staff listings so they don't appear to
                // buyers or demo viewers.
                $table->boolean('hidden')->default(false)->after('has_system_access');
            });
        }

        if (!Schema::hasTable('login_activities')) {
            Schema::create('login_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('email')->nullable()->index();
                $table->string('event', 20)->default('login'); // login | failed | logout
                $table->string('ip_address', 45)->nullable();
                $table->string('country', 80)->nullable();
                $table->string('city', 120)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
        if (Schema::hasColumn('users', 'hidden')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('hidden');
            });
        }
    }
};
