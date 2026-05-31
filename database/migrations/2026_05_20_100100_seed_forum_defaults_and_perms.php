<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed data for the `forum` module:
 *   - Grant every forum.* / messages.* permission to the admin role
 *     (admin already gets "everything" from the catalog flatten in the
 *     roles migration, but that flatten ran BEFORE these keys existed —
 *     so admins on existing installs would be missing them. This
 *     migration grants explicitly).
 *   - Seed a default category + a couple of starter forums so the index
 *     isn't empty on first boot.
 *   - Add forum / messaging events to notification_preferences so the
 *     observer fan-out has rows to consult.
 */
return new class extends Migration {
    public function up(): void
    {
        // ----- 1) Admin grants -----
        $newPerms = [
            'forum.view','forum.post','forum.reply','forum.moderate','forum.admin',
            'messages.send','messages.group',
        ];
        if (Schema::hasTable('roles')) {
            $admin = DB::table('roles')->where('name', 'admin')->first();
            if ($admin) {
                $perms = json_decode((string) $admin->permissions, true) ?: [];
                $perms = array_values(array_unique(array_merge($perms, $newPerms)));
                DB::table('roles')->where('id', $admin->id)->update([
                    'permissions' => json_encode($perms),
                    'updated_at'  => now(),
                ]);
            }
            // Doctors / nurses / receptionists / lab / pharmacy all get
            // baseline forum read + post/reply so they can participate.
            // Admin role retains exclusive moderate/admin.
            $staffBaseline = ['forum.view','forum.post','forum.reply','messages.send'];
            foreach (['doctor','nurse','receptionist','lab','pharmacist'] as $name) {
                $row = DB::table('roles')->where('name', $name)->first();
                if (!$row) continue;
                $perms = json_decode((string) $row->permissions, true) ?: [];
                $perms = array_values(array_unique(array_merge($perms, $staffBaseline)));
                DB::table('roles')->where('id', $row->id)->update([
                    'permissions' => json_encode($perms),
                    'updated_at'  => now(),
                ]);
            }
        }

        // ----- 2) Seed default categories + forums -----
        // Single role list shared by the starter forums. Per-forum
        // customisation happens through the admin UI afterwards.
        $openToAll = json_encode([
            'view'     => ['admin','doctor','nurse','receptionist','lab','pharmacist'],
            'post'     => ['admin','doctor','nurse','receptionist','lab','pharmacist'],
            'reply'    => ['admin','doctor','nurse','receptionist','lab','pharmacist'],
            'moderate' => ['admin'],
        ]);
        $clinicalOnly = json_encode([
            'view'     => ['admin','doctor','nurse'],
            'post'     => ['admin','doctor','nurse'],
            'reply'    => ['admin','doctor','nurse'],
            'moderate' => ['admin','doctor'],
        ]);
        $adminOnly = json_encode([
            'view'     => ['admin'],
            'post'     => ['admin'],
            'reply'    => ['admin'],
            'moderate' => ['admin'],
        ]);

        $generalId = DB::table('forum_categories')->insertGetId([
            'name' => 'General', 'slug' => 'general',
            'description' => 'Day-to-day chat and notices for the whole clinic.',
            'sort_order' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $clinicalId = DB::table('forum_categories')->insertGetId([
            'name' => 'Clinical', 'slug' => 'clinical',
            'description' => 'Doctors & nursing-led case discussions.',
            'sort_order' => 20,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $announceId = DB::table('forum_categories')->insertGetId([
            'name' => 'Announcements', 'slug' => 'announcements',
            'description' => 'Notices the clinic posts publicly for patients.',
            'sort_order' => 30,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('forums')->insert([
            [
                'forum_category_id' => $generalId,
                'name' => 'Staff Lounge', 'slug' => 'staff-lounge',
                'description' => 'General chat, shift swaps, lost & found.',
                'icon' => 'fa-couch', 'sort_order' => 10,
                'allowed_roles' => $openToAll,
                'is_announcements' => false,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'forum_category_id' => $generalId,
                'name' => 'Suggestions & Bugs', 'slug' => 'suggestions',
                'description' => 'Ideas, problems, things to fix in the HMS.',
                'icon' => 'fa-lightbulb', 'sort_order' => 20,
                'allowed_roles' => $openToAll,
                'is_announcements' => false,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'forum_category_id' => $clinicalId,
                'name' => 'Case Discussion', 'slug' => 'case-discussion',
                'description' => 'Anonymised case threads for second opinions.',
                'icon' => 'fa-user-doctor', 'sort_order' => 10,
                'allowed_roles' => $clinicalOnly,
                'is_announcements' => false,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'forum_category_id' => $clinicalId,
                'name' => 'Protocols & Guidelines', 'slug' => 'protocols',
                'description' => 'Clinic SOPs, drug protocols, vaccine schedules.',
                'icon' => 'fa-book-medical', 'sort_order' => 20,
                'allowed_roles' => $clinicalOnly,
                'is_announcements' => false,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'forum_category_id' => $announceId,
                'name' => 'Patient Announcements', 'slug' => 'patient-announcements',
                'description' => 'Posts here are also visible on the public site (no login).',
                'icon' => 'fa-bullhorn', 'sort_order' => 10,
                'allowed_roles' => $adminOnly,
                'is_announcements' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        // ----- 3) Notification preference rows -----
        if (Schema::hasTable('notification_preferences')) {
            $events = [
                ['forum_dm_received',    'Direct message received',                'Forum & Messaging', true,  0,0, 1,1, 'In-app bell always; email + WhatsApp gated here. Recipient toggle is in My Profile.'],
                ['forum_topic_reply',    'Reply to a topic you subscribed to',     'Forum & Messaging', true,  0,0, 1,0, 'Fans out to all subscribers; recipient channel gating applies.'],
                ['forum_announcement',   'New patient announcement published',     'Forum & Messaging', true,  0,0, 1,0, 'Fired when a topic in an announcements forum is published.'],
            ];
            $rows = [];
            foreach ($events as $r) {
                [$key,$label,$group,$enabled,$pe,$pw,$ae,$aw,$note] = $r;
                // Skip if it somehow exists already (re-run safety).
                if (DB::table('notification_preferences')->where('event_key', $key)->exists()) continue;
                $rows[] = [
                    'event_key'        => $key,
                    'event_label'      => $label,
                    'group_label'      => $group,
                    'is_enabled'       => $enabled,
                    'patient_email'    => $pe,
                    'patient_whatsapp' => $pw,
                    'admin_email'      => $ae,
                    'admin_whatsapp'   => $aw,
                    'note'             => $note,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }
            if (!empty($rows)) DB::table('notification_preferences')->insert($rows);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            $newPerms = [
                'forum.view','forum.post','forum.reply','forum.moderate','forum.admin',
                'messages.send','messages.group',
            ];
            foreach (DB::table('roles')->get() as $r) {
                $perms = json_decode((string) $r->permissions, true) ?: [];
                $perms = array_values(array_diff($perms, $newPerms));
                DB::table('roles')->where('id', $r->id)->update([
                    'permissions' => json_encode($perms),
                    'updated_at'  => now(),
                ]);
            }
        }
        if (Schema::hasTable('notification_preferences')) {
            DB::table('notification_preferences')->whereIn('event_key', [
                'forum_dm_received', 'forum_topic_reply', 'forum_announcement',
            ])->delete();
        }
        // Categories/forums dropped by the previous migration's down().
    }
};
