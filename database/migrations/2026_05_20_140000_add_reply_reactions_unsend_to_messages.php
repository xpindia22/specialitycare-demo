<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $t) {
            // Reply/quote target (same thread). Null on parent delete so a
            // reply survives even if the quoted message is removed.
            $t->foreignId('reply_to_message_id')->nullable()->after('user_id')
                ->constrained('messages')->nullOnDelete();
            // "Unsent" marker. We DON'T use SoftDeletes — the row stays so the
            // thread keeps its shape; we just render a "deleted" placeholder.
            $t->timestamp('deleted_at')->nullable()->after('edited_at');
        });

        Schema::create('message_reactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('emoji', 16);
            $t->timestamps();
            $t->unique(['message_id', 'user_id', 'emoji'], 'msg_reaction_uniq');
            $t->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
        Schema::table('messages', function (Blueprint $t) {
            $t->dropConstrainedForeignId('reply_to_message_id');
            $t->dropColumn('deleted_at');
        });
    }
};
