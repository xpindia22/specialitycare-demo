<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echo_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 40)->default('other');
            $table->string('description', 255)->nullable();
            $table->json('fields');
            $table->boolean('is_builtin')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['category', 'is_builtin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echo_templates');
    }
};
