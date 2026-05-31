<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('allergens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('category', 30)->default('other'); // drugs, foods, environmental, other
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed from existing constant
        $seeds = [
            'drugs'         => ['Penicillin','Amoxicillin','Aspirin','Ibuprofen','NSAIDs','Sulpha Drugs','Codeine','Morphine','Tetracycline','Erythromycin','Cephalosporins','ACE Inhibitors','Iodine Contrast','Local Anaesthetics','Metronidazole'],
            'foods'         => ['Peanuts','Tree Nuts','Shellfish','Eggs','Milk','Wheat','Soy'],
            'environmental' => ['Pollen','Dust Mites','Pet Dander','Mold','Latex'],
            'other'         => ['Bee Stings','Nickel','Alcohol'],
        ];
        $rows = [];
        foreach ($seeds as $cat => $names) {
            foreach ($names as $i => $name) {
                $rows[] = ['name' => $name, 'category' => $cat, 'is_active' => 1, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()];
            }
        }
        DB::table('allergens')->insert($rows);
    }

    public function down(): void { Schema::dropIfExists('allergens'); }
};
