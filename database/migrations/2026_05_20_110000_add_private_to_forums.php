<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Private / personal forums.
 *
 * A forum flagged private belongs to exactly one user (private_owner_id).
 * It is invisible to everyone else — not in the forum index, not in the
 * forum-admin list, and direct URLs 404 for non-owners so its existence
 * isn't leaked. The owner must re-enter their OWN account password to
 * open it ("my admin password is the forum password"); the unlock is held
 * in the session and clears on logout.
 *
 * No password is stored on the forum — verification runs Hash::check()
 * against the owner's users.password hash, so changing the account
 * password automatically changes the forum password.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('forums', function (Blueprint $t) {
            $t->boolean('is_private')->default(false)->after('is_announcements');
            $t->foreignId('private_owner_id')->nullable()->after('is_private')
              ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forums', function (Blueprint $t) {
            $t->dropConstrainedForeignId('private_owner_id');
            $t->dropColumn('is_private');
        });
    }
};
