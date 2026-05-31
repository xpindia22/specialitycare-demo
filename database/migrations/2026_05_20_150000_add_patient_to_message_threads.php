<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_threads', function (Blueprint $t) {
            // Optional: a conversation can be "about" a patient. Deep-links to
            // the chart from the thread header. Null = not linked.
            $t->foreignId('patient_id')->nullable()->after('subject')
                ->constrained('patients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('message_threads', function (Blueprint $t) {
            $t->dropConstrainedForeignId('patient_id');
        });
    }
};
