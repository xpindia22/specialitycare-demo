<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $defaults = [
            'shift_morning_start' => '08:00',
            'shift_morning_end'   => '13:00',
            'shift_evening_start' => '16:00',
            'shift_evening_end'   => '18:30',
            'shift_night_start'   => '22:00',
            'shift_night_end'     => '06:00',
        ];
        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'group' => 'shifts', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void {
        DB::table('settings')->whereIn('key', [
            'shift_morning_start','shift_morning_end',
            'shift_evening_start','shift_evening_end',
            'shift_night_start','shift_night_end',
        ])->delete();
    }
};
