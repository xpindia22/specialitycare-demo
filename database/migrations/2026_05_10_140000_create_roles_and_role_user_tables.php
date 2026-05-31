<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->string('label');
            $table->json('permissions');
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        // Seed default roles. Catalog stays in code; defaults are minimal
        // sensible starting points and can be edited via the admin UI.
        $allPerms = collect(config('permissions', []))
            ->flatMap(fn ($g) => array_keys($g['perms'] ?? []))
            ->values()->all();

        $defaults = [
            'admin' => [
                'label'     => 'Administrator',
                'is_system' => true,
                'sort_order'=> 10,
                'perms'     => $allPerms, // admin gets everything
            ],
            'doctor' => [
                'label'     => 'Doctor',
                'is_system' => true,
                'sort_order'=> 20,
                'perms'     => [
                    'patients.view','patients.create','patients.edit',
                    'opd.queue','opd.appointments','opd.encounter','opd.prescription','opd.vaccinations',
                    'ipd.admit','ipd.discharge',
                    'lab.results',
                    'reports.view',
                    'certificates.manage',
                ],
            ],
            'nurse' => [
                'label'     => 'Nurse',
                'is_system' => true,
                'sort_order'=> 30,
                'perms'     => [
                    'patients.view','patients.create','patients.edit',
                    'opd.queue','opd.appointments','opd.encounter','opd.vaccinations',
                    'ipd.admit','ipd.beds','ipd.discharge',
                    'lab.orders',
                    'billing.create',
                    'reports.view',
                    'certificates.manage',
                ],
            ],
            'receptionist' => [
                'label'     => 'Receptionist',
                'is_system' => true,
                'sort_order'=> 40,
                'perms'     => [
                    'patients.view','patients.create','patients.edit',
                    'opd.queue','opd.appointments',
                    'billing.create',
                    'certificates.manage',
                ],
            ],
            'pharmacist' => [
                'label'     => 'Pharmacist',
                'is_system' => true,
                'sort_order'=> 50,
                'perms'     => [
                    'patients.view',
                    'pharmacy.dispense','pharmacy.stock',
                ],
            ],
            'lab' => [
                'label'     => 'Lab Technician',
                'is_system' => true,
                'sort_order'=> 60,
                'perms'     => [
                    'patients.view',
                    'lab.orders','lab.results',
                ],
            ],
        ];

        foreach ($defaults as $name => $r) {
            DB::table('roles')->insert([
                'name'        => $name,
                'label'       => $r['label'],
                'permissions' => json_encode($r['perms']),
                'is_system'   => $r['is_system'],
                'sort_order'  => $r['sort_order'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Backfill role_user pivot from existing User.role string so nothing
        // breaks the moment this migration runs.
        $userRows = DB::table('users')->select('id', 'role')->get();
        $roleByName = DB::table('roles')->pluck('id', 'name');
        foreach ($userRows as $u) {
            $rid = $roleByName[$u->role] ?? null;
            if (!$rid) continue;
            DB::table('role_user')->insertOrIgnore([
                'user_id' => $u->id,
                'role_id' => $rid,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
