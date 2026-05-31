<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $t->string('path');                       // private 'local' disk path
            $t->string('original_name');
            $t->string('mime_type', 150)->nullable();
            $t->unsignedInteger('size')->default(0);  // bytes
            $t->timestamps();
            $t->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
