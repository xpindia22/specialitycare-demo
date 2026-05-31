<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $charges = [
            ['General Ward',     'general',      'Room Rent', 800.00],
            ['Semi-Private Room','semi_private',  'Room Rent', 1500.00],
            ['Private Room',     'private',       'Room Rent', 2500.00],
            ['ICU',              'icu',           'Room Rent', 5000.00],
            ['HDU',              'hdu',           'Room Rent', 3500.00],
            ['Emergency Ward',   'emergency',     'Room Rent', 1200.00],
        ];
        foreach ($charges as [$name, $ward_type, $category, $price]) {
            DB::table('service_charges')->insertOrIgnore([
                'name'        => $name . ' Room Rent',
                'category'    => 'Room Rent',
                'price'       => $price,
                'is_taxable'  => false,
                'tax_percent' => 0,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void {
        DB::table('service_charges')->where('category', 'Room Rent')->delete();
    }
};
