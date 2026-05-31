<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Editable option lists for the prescription form (dose / frequency / route /
 * duration / instruction). Seeded with the previous hard-coded defaults;
 * doctors add to them via "➕ Add new…" so custom values persist and reappear.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rx_options', function (Blueprint $t) {
            $t->id();
            $t->string('field', 20);            // dose|frequency|route|duration|instruction
            $t->string('value', 191);
            $t->string('label', 191)->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
            $t->unique(['field', 'value']);
        });

        $now = now();
        $rows = [];
        $add = function ($field, $value, $label = null) use (&$rows, $now) {
            static $i = 0;
            $rows[] = ['field' => $field, 'value' => $value, 'label' => $label ?: $value, 'sort' => $i++, 'created_at' => $now, 'updated_at' => $now];
        };
        foreach ([['OD','OD — Once Daily'],['BD','BD — Twice Daily'],['TDS','TDS — Thrice Daily'],['QID','QID — Four times/day'],['HS','HS — At Bedtime'],['SOS','SOS — As Needed'],['Stat','STAT — Immediately']] as [$v,$l]) $add('frequency',$v,$l);
        foreach (['Oral','IV','IM','SC','Topical','Inhaled','Sublingual','Rectal'] as $v) $add('route',$v);
        foreach (['Before food','After food','With food','Empty stomach','With warm water','With milk'] as $v) $add('instruction',$v);
        foreach (['1 Tab','2 Tab','½ Tab','1 Cap','5 ml','10 ml','250 mg','500 mg','650 mg','1 g','1 drop','2 drops'] as $v) $add('dose',$v);
        foreach (['3','5','7','10','14','15','21','30'] as $v) $add('duration',$v);

        DB::table('rx_options')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('rx_options');
    }
};
