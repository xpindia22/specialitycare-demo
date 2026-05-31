<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_stock', function (Blueprint $t) {
            $t->string('destroy_method', 100)->nullable()->after('purchase_item_id');
            $t->date('destroyed_on')->nullable()->after('destroy_method');
            $t->string('destroy_witness', 191)->nullable()->after('destroyed_on');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_stock', function (Blueprint $t) {
            $t->dropColumn(['destroy_method', 'destroyed_on', 'destroy_witness']);
        });
    }
};
