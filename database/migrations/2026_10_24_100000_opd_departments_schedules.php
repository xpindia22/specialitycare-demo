<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Multispeciality OPD, phase 1:
// - departments get an icon, colour, room, booking order, online visibility and an
//   optional token prefix (set one to give that department its own token series, C-001…)
// - a doctor can sit in several departments (department_doctor; doctors.department_id stays primary)
// - weekly timetable per doctor (several sessions a day) and leave dates
// - OPD tokens remember their department and numbering series (queue)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'icon'))         $table->string('icon', 40)->nullable()->after('description');
            if (!Schema::hasColumn('departments', 'color'))        $table->string('color', 9)->nullable()->after('icon');
            if (!Schema::hasColumn('departments', 'room'))         $table->string('room', 60)->nullable()->after('color');
            if (!Schema::hasColumn('departments', 'token_prefix')) $table->string('token_prefix', 4)->nullable()->after('room');
            if (!Schema::hasColumn('departments', 'show_online'))  $table->boolean('show_online')->default(true)->after('token_prefix');
            if (!Schema::hasColumn('departments', 'sort_order'))   $table->unsignedSmallInteger('sort_order')->default(100)->after('show_online');
        });

        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'languages'))   $table->string('languages', 150)->nullable()->after('bio');
            if (!Schema::hasColumn('doctors', 'show_online')) $table->boolean('show_online')->default(true)->after('is_active');
        });

        if (!Schema::hasTable('department_doctor')) {
            Schema::create('department_doctor', function (Blueprint $table) {
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
                $table->primary(['department_id', 'doctor_id']);
            });
        }

        if (!Schema::hasTable('doctor_schedules')) {
            Schema::create('doctor_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');            // 0 = Sunday … 6 = Saturday
                $table->time('start_time');
                $table->time('end_time');
                $table->unsignedSmallInteger('slot_minutes')->nullable(); // null = doctor's slot_duration
                $table->unsignedSmallInteger('max_patients')->nullable(); // null = no limit
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete(); // e.g. a Diabetes clinic session
                $table->string('room', 60)->nullable();
                $table->timestamps();
                $table->index(['doctor_id', 'day_of_week']);
            });
        }

        if (!Schema::hasTable('doctor_leaves')) {
            Schema::create('doctor_leaves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
                $table->date('from_date');
                $table->date('to_date');
                $table->string('reason', 150)->nullable();
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
                $table->index(['doctor_id', 'from_date', 'to_date']);
            });
        }

        Schema::table('opd_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('opd_tokens', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('doctor_id')->constrained()->nullOnDelete();
            }
            // Numbering series: '' = the clinic-wide T-series (as before), else a department's prefix
            if (!Schema::hasColumn('opd_tokens', 'queue')) {
                $table->string('queue', 4)->default('')->after('token_no');
            }
        });
        $idx = collect(DB::select("SHOW INDEX FROM opd_tokens WHERE Key_name = 'opd_tokens_token_no_token_date_unique'"));
        if ($idx->isNotEmpty()) {
            Schema::table('opd_tokens', function (Blueprint $table) {
                $table->dropUnique('opd_tokens_token_no_token_date_unique');
                $table->unique(['token_date', 'queue', 'token_no'], 'opd_tokens_date_queue_no_unique');
            });
        }

        $this->seedDepartments();
    }

    /** Full multispeciality list with icons/colours; renames General Medicine to Internal Medicine. */
    private function seedDepartments(): void
    {
        DB::table('departments')->where('code', 'GM')->where('name', 'General Medicine')->update(['name' => 'Internal Medicine']);

        $list = [
            // code => [name, icon, colour, order]
            'GM'     => ['Internal Medicine',  'fa-stethoscope',        '#0f766e', 10],
            'CARD'   => ['Cardiology',         'fa-heart-pulse',        '#dc2626', 20],
            'GASTRO' => ['Gastroenterology',   'fa-bacterium',          '#ca8a04', 30],
            'ONCO'   => ['Oncology',           'fa-ribbon',             '#9333ea', 40],
            'NEPH'   => ['Nephrology',         'fa-droplet',            '#2563eb', 50],
            'URO'    => ['Urology',            'fa-person',             '#0369a1', 60],
            'ENDO'   => ['Endocrinology',      'fa-syringe',            '#ea580c', 70],
            'GYN'    => ['Gynaecology',        'fa-person-pregnant',    '#db2777', 80],
            'PAED'   => ['Paediatrics',        'fa-baby',               '#16a34a', 90],
            'RHEUM'  => ['Rheumatology',       'fa-hand-dots',          '#7c3aed', 100],
            'ORTH'   => ['Orthopaedics',       'fa-bone',               '#78716c', 110],
            'PHYSIO' => ['Physiotherapy',      'fa-person-walking',     '#059669', 120],
            'OPTH'   => ['Ophthalmology',      'fa-eye',                '#0891b2', 130],
            'ENT'    => ['ENT',                'fa-ear-listen',         '#d97706', 140],
            'DERM'   => ['Dermatology',        'fa-hand-sparkles',      '#be185d', 150],
            'NEURO'  => ['Neurology',          'fa-brain',              '#4f46e5', 160],
            'PSYCH'  => ['Psychiatry',         'fa-comments',           '#6d28d9', 170],
            'PULM'   => ['Pulmonology',        'fa-lungs',              '#0284c7', 180],
            'EM'     => ['Emergency Medicine', 'fa-truck-medical',      '#b91c1c', 190],
        ];
        foreach ($list as $code => [$name, $icon, $color, $order]) {
            $row = DB::table('departments')->where('code', $code)->first();
            if ($row) {
                DB::table('departments')->where('id', $row->id)->update([
                    'icon'       => $row->icon ?: $icon,
                    'color'      => $row->color ?: $color,
                    'sort_order' => $row->sort_order != 100 ? $row->sort_order : $order,
                ]);
            } else {
                DB::table('departments')->insert([
                    'name' => $name, 'code' => $code, 'icon' => $icon, 'color' => $color, 'sort_order' => $order,
                    'is_active' => true, 'show_online' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('opd_tokens', function (Blueprint $table) {
            $table->dropUnique('opd_tokens_date_queue_no_unique');
            $table->unique(['token_no', 'token_date']);
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('queue');
        });
        Schema::dropIfExists('doctor_leaves');
        Schema::dropIfExists('doctor_schedules');
        Schema::dropIfExists('department_doctor');
        Schema::table('doctors', fn (Blueprint $t) => $t->dropColumn(['languages', 'show_online']));
        Schema::table('departments', fn (Blueprint $t) => $t->dropColumn(['icon', 'color', 'room', 'token_prefix', 'show_online', 'sort_order']));
    }
};
