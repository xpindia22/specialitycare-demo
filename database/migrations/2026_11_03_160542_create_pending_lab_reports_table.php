<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pending_lab_reports', function (Blueprint $t) {
            $t->id();
            $t->string('sender')->nullable();            // WhatsApp number it came from
            $t->string('channel', 20)->default('whatsapp'); // whatsapp|upload|manual
            $t->string('report_path');                   // stored PDF
            $t->string('source', 10)->default('text');   // text|ocr
            $t->string('report_name')->nullable();       // name parsed off the PDF
            $t->string('reg_id')->nullable();
            $t->date('sample_date')->nullable();
            $t->json('parsed')->nullable();              // full parsed payload (header+analytes)
            $t->unsignedInteger('analyte_count')->default(0);
            $t->string('status', 20)->default('Pending'); // Pending|Imported|Rejected
            $t->foreignId('patient_id')->nullable();
            $t->foreignId('lab_order_id')->nullable();
            $t->foreignId('handled_by')->nullable();
            $t->timestamp('handled_at')->nullable();
            $t->timestamps();
            $t->index(['status','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('pending_lab_reports'); }
};
