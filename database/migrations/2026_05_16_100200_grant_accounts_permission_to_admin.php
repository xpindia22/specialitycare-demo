<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add the accounts.manage permission to the admin role's JSON permissions
 * array. Roles store permissions as a JSON list of strings.
 */
return new class extends Migration {

    public function up(): void
    {
        DB::table('roles')->orderBy('id')->each(function ($role) {
            if ($role->name !== 'admin') return;
            $perms = json_decode($role->permissions ?: '[]', true) ?: [];
            if (!in_array('accounts.manage', $perms, true)) {
                $perms[] = 'accounts.manage';
                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values(array_unique($perms))),
                    'updated_at'  => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('roles')->orderBy('id')->each(function ($role) {
            if ($role->name !== 'admin') return;
            $perms = json_decode($role->permissions ?: '[]', true) ?: [];
            $perms = array_values(array_filter($perms, fn($p) => $p !== 'accounts.manage'));
            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($perms),
                'updated_at'  => now(),
            ]);
        });
    }
};
