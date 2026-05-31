<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('contact_email');
        });

        Schema::create('issued_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->json('modules');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();

            // Payment tracking
            $table->decimal('payment_amount', 12, 2)->nullable();
            $table->string('payment_currency', 8)->default('INR');
            $table->date('payment_received_at')->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->text('payment_notes')->nullable();

            // The signed artifact (so we can re-download anytime without resigning)
            $table->longText('license_json');
            $table->text('license_sig');
            $table->string('public_key_fingerprint', 16)->nullable();

            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['buyer_id', 'issued_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_licenses');
        Schema::dropIfExists('buyers');
    }
};
