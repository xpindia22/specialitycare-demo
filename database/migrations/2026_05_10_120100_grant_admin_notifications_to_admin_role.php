<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Grant the new 'admin.notifications' permission to the built-in 'admin'
 * role on existing installs. New installs already get it via the roles
 * seeder once it's added there. Idempotent.
 */
return new class extends Migration {
    public function up(): void
    {
        $rows = DB::table('roles')->where('name', 'admin')->get(['id', 'permissions']);
        foreach ($rows as $row) {
            $perms = json_decode($row->permissions ?? '[]', true) ?: [];
            if (!in_array('admin.notifications', $perms, true)) {
                $perms[] = 'admin.notifications';
                DB::table('roles')->where('id', $row->id)->update([
                    'permissions' => json_encode($perms),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $rows = DB::table('roles')->where('name', 'admin')->get(['id', 'permissions']);
        foreach ($rows as $row) {
            $perms = json_decode($row->permissions ?? '[]', true) ?: [];
            $perms = array_values(array_filter($perms, fn ($p) => $p !== 'admin.notifications'));
            DB::table('roles')->where('id', $row->id)->update([
                'permissions' => json_encode($perms),
                'updated_at'  => now(),
            ]);
        }
    }
};
