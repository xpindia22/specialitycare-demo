<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Settings
        $settings = [
            ['key' => 'clinic_name',    'value' => 'SpecialityCare Hospital',         'group' => 'clinic'],
            ['key' => 'clinic_address', 'value' => 'Port Blair, Andaman & Nicobar',   'group' => 'clinic'],
            ['key' => 'clinic_phone',   'value' => '+91-3192-000000',                 'group' => 'clinic'],
            ['key' => 'clinic_email',   'value' => 'info@specialitycare.com',         'group' => 'clinic'],
            ['key' => 'patient_prefix', 'value' => 'JHC',                            'group' => 'clinic'],
            ['key' => 'currency',       'value' => '₹',                              'group' => 'billing'],
            ['key' => 'clinic_logo',    'value' => '',                               'group' => 'clinic'],
            ['key' => 'footer_text',    'value' => 'Thank you for choosing SpecialityCare Hospital', 'group' => 'billing'],
        ];
        foreach ($settings as $s) {
            DB::table('settings')->updateOrInsert(['key' => $s['key']], $s);
        }

        // Departments
        $departments = [
            ['name' => 'General Medicine',    'code' => 'GM'],
            ['name' => 'Cardiology',          'code' => 'CARD'],
            ['name' => 'Orthopaedics',        'code' => 'ORTH'],
            ['name' => 'Gynaecology',         'code' => 'GYN'],
            ['name' => 'Paediatrics',         'code' => 'PAED'],
            ['name' => 'ENT',                 'code' => 'ENT'],
            ['name' => 'Ophthalmology',       'code' => 'OPTH'],
            ['name' => 'Dermatology',         'code' => 'DERM'],
            ['name' => 'Neurology',           'code' => 'NEURO'],
            ['name' => 'Psychiatry',          'code' => 'PSYCH'],
            ['name' => 'Pulmonology',         'code' => 'PULM'],
            ['name' => 'Gastroenterology',    'code' => 'GASTRO'],
            ['name' => 'Nephrology',          'code' => 'NEPH'],
            ['name' => 'Endocrinology',       'code' => 'ENDO'],
            ['name' => 'Emergency Medicine',  'code' => 'EM'],
        ];
        foreach ($departments as $d) {
            DB::table('departments')->updateOrInsert(['code' => $d['code']], $d + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Admin user
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@specialitycare.com'],
            ['name' => 'Admin', 'password' => Hash::make('Admin@123'), 'role' => 'admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
        );

        // Doctor user
        DB::table('users')->updateOrInsert(
            ['email' => 'doctor@specialitycare.com'],
            ['name' => 'Dr. Sample Doctor', 'password' => Hash::make('Doctor@123'), 'role' => 'doctor', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
        );

        // Medicine categories
        $medCats = [
            'Antibiotics', 'Analgesics', 'Antihypertensives', 'Antidiabetics',
            'Antacids', 'Antihistamines', 'Vitamins & Supplements', 'Cardiovascular',
            'Respiratory', 'Neurological', 'Hormones', 'Antifungals',
            'Antivirals', 'Dermatological', 'Ophthalmic',
        ];
        foreach ($medCats as $cat) {
            DB::table('medicine_categories')->updateOrInsert(['name' => $cat], ['name' => $cat, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Lab categories
        $labCats = [
            ['name' => 'Haematology',       'code' => 'HAEM'],
            ['name' => 'Biochemistry',      'code' => 'BIO'],
            ['name' => 'Microbiology',      'code' => 'MICRO'],
            ['name' => 'Immunology',        'code' => 'IMMUNO'],
            ['name' => 'Hormones',          'code' => 'HORM'],
            ['name' => 'Urine Analysis',    'code' => 'URINE'],
            ['name' => 'Stool Analysis',    'code' => 'STOOL'],
            ['name' => 'Radiology',         'code' => 'RAD'],
            ['name' => 'Cardiac Markers',   'code' => 'CARD'],
            ['name' => 'Tumour Markers',    'code' => 'TUMOUR'],
        ];
        foreach ($labCats as $cat) {
            DB::table('lab_categories')->updateOrInsert(['code' => $cat['code']], $cat + ['created_at' => now(), 'updated_at' => now()]);
        }

        // Service charges
        $services = [
            ['name' => 'General Consultation',    'category' => 'Consultation', 'price' => 300],
            ['name' => 'Specialist Consultation', 'category' => 'Consultation', 'price' => 500],
            ['name' => 'Emergency Consultation',  'category' => 'Consultation', 'price' => 800],
            ['name' => 'Follow-up Consultation',  'category' => 'Consultation', 'price' => 200],
            ['name' => 'ECG',                     'category' => 'Investigation', 'price' => 250],
            ['name' => 'Echocardiogram',          'category' => 'Investigation', 'price' => 1500],
            ['name' => 'Dressing - Simple',       'category' => 'Procedure',    'price' => 150],
            ['name' => 'Dressing - Complex',      'category' => 'Procedure',    'price' => 300],
            ['name' => 'Injection (IV)',          'category' => 'Procedure',    'price' => 100],
            ['name' => 'Injection (IM)',          'category' => 'Procedure',    'price' => 80],
            ['name' => 'Nebulization',            'category' => 'Procedure',    'price' => 150],
            ['name' => 'Suture Removal',          'category' => 'Procedure',    'price' => 100],
        ];
        foreach ($services as $s) {
            DB::table('service_charges')->updateOrInsert(['name' => $s['name']], $s + ['is_active' => true, 'is_taxable' => false, 'tax_percent' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->call(MedicineLabMasterSeeder::class);
    }
}
