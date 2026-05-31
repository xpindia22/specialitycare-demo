<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Each buyer gets their OWN ed25519 keypair so that a license
            // signed for one buyer cannot validate on another buyer's
            // install (their public keys differ).
            $table->text('public_key')->nullable()->after('notes');   // base64, plain
            $table->text('private_key')->nullable()->after('public_key'); // encrypted via Eloquent cast
            $table->timestamp('keypair_generated_at')->nullable()->after('private_key');
        });

        Schema::create('license_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_license_id')->constrained()->cascadeOnDelete();
            $table->string('install_id', 64);
            $table->string('host', 200)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('app_version', 30)->nullable();
            $table->timestamp('created_at');

            $table->index(['issued_license_id', 'install_id', 'created_at']);
            $table->index(['install_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_heartbeats');
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn(['public_key', 'private_key', 'keypair_generated_at']);
        });
    }
};
