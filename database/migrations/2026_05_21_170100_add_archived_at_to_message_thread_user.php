<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user conversation archiving. archived_at on the participant pivot lets
 * a user hide a thread from their inbox (and restore it) without affecting
 * anyone else or deleting anything. Null = not archived.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_thread_user', function (Blueprint $t) {
            $t->timestamp('archived_at')->nullable()->after('left_at');
        });
    }

    public function down(): void
    {
        Schema::table('message_thread_user', function (Blueprint $t) {
            $t->dropColumn('archived_at');
        });
    }
};
