<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient forum portal login (phone + desk-issued PIN).
 *
 * Patients are NOT staff users — they authenticate against this table via a
 * dedicated `patient` guard. The PIN is bcrypt-hashed; the plaintext is only
 * ever shown once to reception at issue time and never stored.
 *
 * Login id = phone. A given phone may belong to only one portal-enabled
 * patient (enforced in the controller at enable time).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $t) {
            $t->boolean('portal_enabled')->default(false)->after('is_active');
            $t->string('portal_pin')->nullable()->after('portal_enabled');     // bcrypt hash
            $t->timestamp('portal_pin_set_at')->nullable()->after('portal_pin');
            $t->index(['phone', 'portal_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $t) {
            $t->dropIndex(['phone', 'portal_enabled']);
            $t->dropColumn(['portal_enabled', 'portal_pin', 'portal_pin_set_at']);
        });
    }
};
