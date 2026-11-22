<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | ICU charting: extra observation fields on ipd_vitals (one vitals source for
 | ward and ICU), a stored NEWS2 score, and running infusions with a log of
 | every start / rate change / stop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipd_vitals', function (Blueprint $t) {
            $t->smallInteger('map')->unsigned()->nullable()->after('bp_diastolic');     // arterial-line MAP; else calculated
            $t->smallInteger('cvp')->nullable()->after('map');
            $t->string('rhythm', 40)->nullable()->after('cvp');
            $t->char('avpu', 1)->nullable()->after('spo2');                           // A, C (new confusion), V, P, U
            $t->tinyInteger('gcs_e')->unsigned()->nullable()->after('avpu');
            $t->tinyInteger('gcs_v')->unsigned()->nullable()->after('gcs_e');
            $t->tinyInteger('gcs_m')->unsigned()->nullable()->after('gcs_v');
            $t->boolean('intubated')->default(false)->after('gcs_m');
            $t->string('pupils', 40)->nullable()->after('intubated');
            $t->tinyInteger('rass')->nullable()->after('pupils');
            $t->tinyInteger('pain_score')->unsigned()->nullable()->after('rass');
            $t->string('o2_device', 20)->nullable()->after('pain_score');             // key of IpdVitals::O2_DEVICES
            $t->decimal('o2_flow', 4, 1)->nullable()->after('o2_device');
            $t->tinyInteger('fio2')->unsigned()->nullable()->after('o2_flow');
            $t->boolean('spo2_scale2')->default(false)->after('fio2');               // NEWS2 hypercapnic scale (target 88–92)
            $t->string('vent_mode', 20)->nullable()->after('spo2_scale2');
            $t->smallInteger('tidal_volume')->unsigned()->nullable()->after('vent_mode');
            $t->tinyInteger('vent_rate')->unsigned()->nullable()->after('tidal_volume');
            $t->decimal('peep', 4, 1)->nullable()->after('vent_rate');
            $t->tinyInteger('pip')->unsigned()->nullable()->after('peep');
            $t->tinyInteger('news2')->unsigned()->nullable()->after('pip');
            $t->boolean('news2_partial')->default(false)->after('news2');
            $t->index(['admission_id', 'recorded_at']);
        });

        Schema::create('icu_infusions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $t->string('drug_key', 40)->nullable();                                    // config('icu_drugs.infusions') key; null = custom
            $t->string('drug_name', 120);
            $t->decimal('amount', 10, 3);                                              // drug in the syringe / bag
            $t->string('amount_unit', 5);                                              // mg, mcg, U
            $t->decimal('volume_ml', 7, 1);
            $t->string('diluent', 20)->nullable();
            $t->string('dose_unit', 16);                                               // e.g. mcg/kg/min
            $t->decimal('weight_kg', 5, 1)->nullable();
            $t->decimal('dose', 10, 4);                                                // current dose
            $t->decimal('rate_ml_h', 7, 2);                                            // current rate
            $t->dateTime('started_at');
            $t->dateTime('stopped_at')->nullable();
            $t->string('notes', 300)->nullable();
            $t->foreignId('started_by')->constrained('users');
            $t->foreignId('stopped_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->index(['admission_id', 'stopped_at']);
        });

        Schema::create('icu_infusion_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('icu_infusion_id')->constrained()->cascadeOnDelete();
            $t->dateTime('logged_at');
            $t->string('action', 16);                                                  // start, change, new_syringe, stop
            $t->decimal('dose', 10, 4)->nullable();
            $t->decimal('rate_ml_h', 7, 2)->nullable();
            $t->string('note', 300)->nullable();
            $t->foreignId('user_id')->constrained('users');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_infusion_logs');
        Schema::dropIfExists('icu_infusions');
        Schema::table('ipd_vitals', function (Blueprint $t) {
            $t->dropIndex(['admission_id', 'recorded_at']);
            $t->dropColumn(['map', 'cvp', 'rhythm', 'avpu', 'gcs_e', 'gcs_v', 'gcs_m', 'intubated', 'pupils', 'rass', 'pain_score',
                'o2_device', 'o2_flow', 'fio2', 'spo2_scale2', 'vent_mode', 'tidal_volume', 'vent_rate', 'peep', 'pip', 'news2', 'news2_partial']);
        });
    }
};
