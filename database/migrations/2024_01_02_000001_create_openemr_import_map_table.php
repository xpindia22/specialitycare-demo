<?php
// FILE: database/migrations/2024_01_02_000001_create_openemr_import_map_table.php
//
// This table is the idempotency key store for the OpenEMR importer.
// It maps every OpenEMR entity (patient, encounter, lab_order, …) to
// the corresponding SpecialityCare primary key so the importer can be
// run multiple times without creating duplicates.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openemr_import_map', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 60)
                  ->comment('e.g. patient, encounter, lab_order, bill');
            $table->string('openemr_id', 80)
                  ->comment('Primary key in the OpenEMR database');
            $table->unsignedBigInteger('sc_id')
                  ->comment('Primary key in SpecialityCare');
            $table->timestamp('imported_at')->useCurrent();

            $table->unique(['entity', 'openemr_id'], 'uq_entity_openemr');
            $table->index('sc_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openemr_import_map');
    }
};
