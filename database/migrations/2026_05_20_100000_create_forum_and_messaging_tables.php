<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables for the `forum` paid module:
 *
 *   forum_categories      Top-level grouping (just a heading on the index)
 *   forums                A board within a category. Carries allowed_roles
 *                         JSON: { view: [...], post: [...], reply: [...],
 *                         moderate: [...] } — role names from the roles table.
 *   forum_topics          A thread inside a forum. is_public toggles the
 *                         patient-facing read-only /announcements page.
 *   forum_posts           Individual posts (the topic's first post and all
 *                         replies). Stored separately so editing is cheap.
 *   forum_subscriptions   User opted into reply notifications on a topic.
 *
 *   message_threads       1-to-1 or group DM thread. is_group differentiates.
 *   message_thread_user   Participants pivot (with read state, mute, leave).
 *   messages              Individual messages in a thread.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('forum_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('slug', 140)->unique();
            $t->string('description', 500)->nullable();
            $t->unsignedSmallInteger('sort_order')->default(100);
            $t->timestamps();
        });

        Schema::create('forums', function (Blueprint $t) {
            $t->id();
            $t->foreignId('forum_category_id')->constrained('forum_categories')->cascadeOnDelete();
            $t->string('name', 160);
            $t->string('slug', 180)->unique();
            $t->string('description', 800)->nullable();
            $t->string('icon', 60)->nullable();
            $t->unsignedSmallInteger('sort_order')->default(100);
            // Per-forum permissions: which role names can view / post / reply / moderate.
            // Shape: { "view": ["admin","doctor","nurse"], "post": [...], "reply": [...], "moderate": ["admin"] }
            $t->json('allowed_roles');
            // When true, ALL topics in this forum are visible read-only on
            // the public /announcements page. Overrides per-topic is_public.
            $t->boolean('is_announcements')->default(false);
            // Counters denormalised so the index doesn't N+1.
            $t->unsignedInteger('topics_count')->default(0);
            $t->unsignedInteger('posts_count')->default(0);
            $t->unsignedBigInteger('last_post_id')->nullable();
            $t->timestamp('last_post_at')->nullable();
            $t->timestamps();
        });

        Schema::create('forum_topics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('forum_id')->constrained('forums')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('title', 200);
            $t->string('slug', 220)->unique();
            $t->boolean('is_pinned')->default(false);
            $t->boolean('is_locked')->default(false);
            // Per-topic public flag. When the parent forum is_announcements=1
            // this is implicit; when not, the admin can still flip a single
            // topic public (the "patient announcements broadcast" path).
            $t->boolean('is_public')->default(false);
            $t->unsignedInteger('posts_count')->default(0);
            $t->unsignedInteger('views_count')->default(0);
            $t->unsignedBigInteger('last_post_id')->nullable();
            $t->timestamp('last_post_at')->nullable();
            $t->timestamps();

            $t->index(['forum_id', 'is_pinned', 'last_post_at'], 'forum_topics_listing_idx');
            $t->index('is_public');
        });

        Schema::create('forum_posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('forum_topic_id')->constrained('forum_topics')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->text('body');
            $t->boolean('is_first')->default(false);  // The topic's opening post
            $t->timestamp('edited_at')->nullable();
            $t->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['forum_topic_id', 'created_at']);
        });

        Schema::create('forum_subscriptions', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('forum_topic_id')->constrained('forum_topics')->cascadeOnDelete();
            $t->timestamp('subscribed_at')->useCurrent();
            $t->primary(['user_id', 'forum_topic_id']);
        });

        Schema::create('message_threads', function (Blueprint $t) {
            $t->id();
            // For group threads the creator names the thread; for 1-to-1
            // it's derived from the other participant's name at display time.
            $t->string('subject', 200)->nullable();
            $t->boolean('is_group')->default(false);
            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $t->unsignedBigInteger('last_message_id')->nullable();
            $t->timestamp('last_message_at')->nullable();
            $t->timestamps();
        });

        Schema::create('message_thread_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('message_thread_id')->constrained('message_threads')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('last_read_at')->nullable();
            $t->boolean('is_muted')->default(false);
            $t->timestamp('left_at')->nullable();
            $t->timestamps();

            $t->unique(['message_thread_id', 'user_id'], 'msg_thread_user_uniq');
            $t->index(['user_id', 'left_at']);
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('message_thread_id')->constrained('message_threads')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->text('body');
            $t->timestamp('edited_at')->nullable();
            $t->timestamps();

            $t->index(['message_thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_thread_user');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('forum_subscriptions');
        Schema::dropIfExists('forum_posts');
        Schema::dropIfExists('forum_topics');
        Schema::dropIfExists('forums');
        Schema::dropIfExists('forum_categories');
    }
};
