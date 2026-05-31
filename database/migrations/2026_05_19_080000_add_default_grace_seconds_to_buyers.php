<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Per-buyer override for the dead-man heartbeat grace window.
            // Null = use the install-side config default. 0 = disable the
            // dead-man check for this buyer entirely. Positive = number of
            // seconds without a successful heartbeat after which modules lock.
            // Value is copied into every signed license's payload, so a buyer
            // can't tamper with it on their install — they'd break the
            // signature and lock themselves out.
            $table->unsignedInteger('default_grace_seconds')->nullable()->after('private_key');
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn('default_grace_seconds');
        });
    }
};
