<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('clinical_chips', function (Blueprint $table) {
            $table->id();
            $table->string('field_key', 40)->index(); // chief_complaint, history_of_present_illness, examination_findings, impression, plan
            $table->string('text', 200);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['field_key', 'text']);
        });

        $seeds = [
            'chief_complaint' => [
                'Fever x 3 days', 'Cough with sputum', 'Sore throat', 'Burning micturition',
                'Headache', 'Body ache', 'Vomiting', 'Loose stools', 'Abdominal pain',
                'Chest pain', 'Breathlessness', 'Palpitations', 'Joint pain', 'Back pain',
                'Routine review', 'Follow-up', 'For prescription refill',
            ],
            'history_of_present_illness' => [
                'Onset: gradual', 'Onset: sudden', 'Duration: __ days', 'Aggravating factors: __',
                'Relieving factors: rest / medication', 'No fever', 'No vomiting',
                'No bowel/bladder changes', 'No weight loss', 'Sleep & appetite preserved',
            ],
            'examination_findings' => [
                'GC: fair, conscious, oriented', 'Afebrile, hydration adequate', 'No pallor / icterus / cyanosis / clubbing / oedema',
                'CVS: S1 S2 normal, no murmur', 'RS: clear, equal air entry, no added sounds',
                'P/A: soft, non-tender, no organomegaly, BS+', 'CNS: GCS 15, no focal deficit',
                'Throat: congested', 'Tonsils enlarged', 'No lymphadenopathy',
            ],
            'impression' => [
                'Likely viral / self-limiting', 'Bacterial — needs antibiotics', 'Chronic — for long-term management',
                'Stable / well-controlled', 'Partially controlled', 'For investigations to confirm',
            ],
            'plan' => [
                'Symptomatic management', 'Antibiotics x 5 days', 'Investigations sent', 'Review with reports',
                'Continue current medication', 'Lifestyle advice given', 'Follow-up in 1 week',
                'Follow-up in 1 month', 'Refer if not improving in 48-72 h', 'Red flags discussed',
            ],
        ];

        $rows = [];
        foreach ($seeds as $key => $items) {
            foreach ($items as $i => $text) {
                $rows[] = [
                    'field_key'  => $key,
                    'text'       => $text,
                    'sort_order' => $i,
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('clinical_chips')->insert($rows);
    }

    public function down(): void { Schema::dropIfExists('clinical_chips'); }
};
