<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Deleting a visit hides it (soft delete) instead of removing it: who, when and why
// are kept, and the owner can view, restore or permanently delete it from
// Deleted Visits. users.can_delete_visits lets the owner give the delete button
// to individual staff.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            if (!Schema::hasColumn('encounters', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->index();
            }
            if (!Schema::hasColumn('encounters', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
            }
            if (!Schema::hasColumn('encounters', 'delete_reason')) {
                $table->string('delete_reason', 255)->nullable();
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'can_delete_visits')) {
                $table->boolean('can_delete_visits')->default(false)->after('has_system_access');
            }
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deleted_by', 'delete_reason']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_delete_visits');
        });
    }
};
