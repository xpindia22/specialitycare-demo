<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Medicine;
use App\Models\PharmacyStock;

class AdditionalMedicineStockSeeder extends Seeder
{
    /**
     * Adds 10,000 units of stock for every medicine added by
     * AdditionalMedicinesSeeder so that the encounter / prescription
     * templates that reference these drugs do not fire the
     * "out-of-stock — please inform pharmacy" alert.
     *
     * Idempotent: keyed on (medicine_id, batch_number = 'INIT-SEED-10K').
     */
    public function run(): void
    {
        $names = [
            // Immunomodulators / DMARDs
            'Tab Methotrexate 7.5mg',
            'Tab Methotrexate 10mg',
            'Tab Hydroxychloroquine 200mg',
            'Tab Leflunomide 10mg',
            'Tab Leflunomide 20mg',
            'Tab Mycophenolate Mofetil 500mg',
            'Tab Sulfasalazine 500mg',
            'Tab Prednisolone 5mg',
            'Tab Prednisolone 10mg',
            'Tab Colchicine 0.5mg',
            'Tab Allopurinol 100mg',
            'Tab Febuxostat 40mg',

            // Cardiology / HF / anticoag
            'Tab Bisoprolol 2.5mg',
            'Tab Bisoprolol 5mg',
            'Tab Eplerenone 25mg',
            'Tab Sacubitril + Valsartan 50mg',
            'Tab Sacubitril + Valsartan 100mg',
            'Tab Dapagliflozin 10mg',
            'Tab Empagliflozin 10mg',
            'Tab Apixaban 5mg',
            'Tab Rivaroxaban 20mg',

            // Endocrine
            'Tab Carbimazole 5mg',
            'Tab Carbimazole 10mg',
            'Tab Pioglitazone 15mg',
            'Tab Pioglitazone 30mg',
            'Tab Vitamin E 400mg',

            // Liver
            'Tab Ursodeoxycholic Acid 300mg',
            'Tab Rifaximin 550mg',
            'Syp Lactulose 200ml',
            'Tab Propranolol 40mg',

            // Kidney
            'Tab Cholecalciferol 60K IU',
            'Tab Calcitriol 0.25mcg',
            'Tab Sevelamer 800mg',
            'Tab Calcium Acetate 667mg',
            'Tab Tamsulosin 0.4mg',
            'Tab Diclofenac 50mg',

            // Neurology
            'Tab Levetiracetam 500mg',
            'Tab Sodium Valproate 500mg',
            'Tab Carbamazepine 200mg',

            // Respiratory / Antibiotics
            'MDI Tiotropium 18mcg',
            'Cap Doxycycline 100mg',
            'Tab Amoxicillin + Clavulanate 625mg',
            'Tab Clindamycin 300mg',
            'MDI Budesonide + Formoterol 200/6mcg',

            // Dermatology / topicals
            'Gel Adapalene 0.1%',
            'Gel Benzoyl Peroxide 2.5%',
            'Cream Mometasone 0.1%',
            'Cream Hydrocortisone 1%',
            'Cream Clotrimazole 1%',
            'Cream Terbinafine 1%',
            'Tab Itraconazole 100mg',
            'Tab Fluconazole 150mg',
            'Tab Fexofenadine 180mg',

            // Orthopedics / pain
            'Gel Diclofenac 1%',
            'Tab Tramadol 50mg',
            'Tab Thiocolchicoside 4mg',
            'Tab Eperisone 50mg',
            'Tab Glucosamine 750mg + Chondroitin 600mg',

            // Gynae / Obs
            'Pessary Clotrimazole 100mg',
            'Tab Ethinyl Estradiol 0.03mg + Drospirenone 3mg',
            'Tab Doxylamine 10mg + Pyridoxine 10mg',
            'Tab Ondansetron 4mg',

            // Dental
            'Mouthwash Chlorhexidine 0.2%',
            'Tab Metronidazole 400mg',

            // Psychiatry
            'Tab Sertraline 50mg',
            'Tab Buspirone 5mg',

            // Top-ups
            'Sachet Potassium Citrate',
            'Tab Ferrous Ascorbate 100mg',
            'Tab Calcium Carbonate 500mg + Vitamin D3 250 IU',
            'Tab Sumatriptan 50mg',
            'Tab Naproxen 500mg',
        ];

        $batch = 'INIT-SEED-10K';
        $expiry = now()->addYears(2)->toDateString();
        $created = 0;
        $skipped = 0;

        foreach ($names as $name) {
            $med = Medicine::where('name', $name)->first();
            if (!$med) { $skipped++; continue; }

            PharmacyStock::firstOrCreate(
                ['medicine_id' => $med->id, 'batch_number' => $batch],
                [
                    'quantity'        => 10000,
                    'expiry_date'     => $expiry,
                    'min_stock_level' => 100,
                    'unit_cost'       => 0,
                    'supplier'        => 'Initial seed',
                    'notes'           => 'Bulk seed stock to silence out-of-stock alerts on template drugs.',
                    'is_active'       => true,
                ]
            );
            $created++;
        }

        $this->command->info("Stock-seeded {$created} medicines (10,000 each, batch {$batch}). Skipped {$skipped} not found.");
    }
}
