<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user "starred"/bookmarked messages. A user stars a message to find it
 * again later (see MessageController::toggleStar / starred). One row per
 * (user, message); both cascade so stars vanish with the user or message.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_stars', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $t->timestamp('created_at')->nullable();
            $t->unique(['user_id', 'message_id']);
            $t->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_stars');
    }
};
