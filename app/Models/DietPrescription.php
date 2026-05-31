<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietPrescription extends Model
{
    protected $fillable = [
        'patient_id','admission_id','encounter_id',
        'height_cm','weight_kg','bmi','target_weight_kg',
        'activity_level','conditions','calorie_target',
        'diet_notes','prescribed_by','prescribed_at',
    ];

    protected $casts = [
        'conditions'    => 'array',
        'prescribed_at' => 'datetime',
        'height_cm'     => 'decimal:1',
        'weight_kg'     => 'decimal:1',
        'bmi'           => 'decimal:1',
        'target_weight_kg' => 'decimal:1',
    ];

    public function patient()     { return $this->belongsTo(Patient::class); }
    public function admission()   { return $this->belongsTo(Admission::class); }
    public function encounter()   { return $this->belongsTo(Encounter::class); }
    public function prescribedBy(){ return $this->belongsTo(User::class, 'prescribed_by'); }

    const ACTIVITY_LEVELS = [
        'sedentary' => ['label' => 'Sedentary (bed/desk rest)',      'factor' => 1.2],
        'light'     => ['label' => 'Light (walking, light activity)', 'factor' => 1.375],
        'moderate'  => ['label' => 'Moderate (exercise 3-5×/week)',   'factor' => 1.55],
        'active'    => ['label' => 'Active (heavy work / sport)',      'factor' => 1.725],
    ];

    const CONDITIONS = [
        'dm'          => ['label' => 'Diabetes Mellitus (DM)',       'icon' => 'fa-droplet',          'color' => '#0ea5e9'],
        'htn'         => ['label' => 'Hypertension (HTN)',           'icon' => 'fa-heart-pulse',      'color' => '#ef4444'],
        'cad'         => ['label' => 'Coronary Artery Disease (CAD)','icon' => 'fa-heart',            'color' => '#dc2626'],
        'cholesterol' => ['label' => 'High Cholesterol',             'icon' => 'fa-flask',            'color' => '#f59e0b'],
        'uric_acid'   => ['label' => 'High Uric Acid / Gout',        'icon' => 'fa-joint',            'color' => '#8b5cf6'],
        'hypothyroid' => ['label' => 'Hypothyroidism',               'icon' => 'fa-bolt',             'color' => '#10b981'],
        'ckd'         => ['label' => 'Chronic Kidney Disease (CKD)', 'icon' => 'fa-kidneys',          'color' => '#6366f1'],
        'obesity'     => ['label' => 'Obesity',                      'icon' => 'fa-weight-scale',     'color' => '#f97316'],
    ];

    // Per-condition diet details used by the view
    const DIET_PLANS = [
        'dm' => [
            'title'       => 'Diabetic Diet',
            'macro'       => '50% Carb · 25% Protein · 25% Fat',
            'key_points'  => [
                'Eat 5–6 small meals at fixed times every day',
                'Choose low glycaemic index (GI) foods — millets, oats, brown rice',
                'Limit each meal to 30–45 g of complex carbohydrates',
                'Avoid sugar, honey, fruit juice, maida, white rice, white bread',
                'Include 2–3 servings of non-starchy vegetables (raw/cooked) at every meal',
                'Preferred proteins: eggs, low-fat dairy, pulses, fish, skinless chicken',
                'Restrict saturated fats; use 2–3 tsp vegetable oil/day',
                'Monitor portion size strictly; use a smaller plate',
            ],
            'avoid'       => ['Sugar & sweets','Sugary drinks & fruit juices','White rice','Maida / refined flour','Potatoes','Packaged snacks','Alcohol','Full-fat dairy'],
            'include'     => ['Bitter gourd','Fenugreek','Oats / Ragi / Jowar','Brown rice','Whole pulses (dal, rajma, chana)','Leafy greens','Eggs / Fish / Chicken (skinless)','Low-fat curd / skimmed milk'],
            'meal_plan'   => [
                'Early Morning (6–7 am)'  => '1 glass warm water with methi seeds soaked overnight; 5–6 soaked almonds',
                'Breakfast (8–9 am)'      => '2 small oats idli / 2 ragi dosa / 1 bowl oatmeal with seeds; 1 cup low-fat milk (no sugar)',
                'Mid-Morning (11 am)'     => '1 small fruit (guava / papaya / orange) — avoid mango, banana, grapes; 1 glass buttermilk',
                'Lunch (1–2 pm)'          => '1½ cup brown rice / 3 small chapatis (whole wheat); 1 cup dal; 1 cup sabzi; salad; low-fat curd',
                'Evening Snack (4–5 pm)'  => 'Handful of roasted chana / 1 boiled egg / sprouts chaat; 1 cup green tea (no sugar)',
                'Dinner (7–8 pm)'         => '2 small whole-wheat chapatis; 1 cup dal or sabzi; salad; 1 cup skimmed milk at bedtime (optional)',
            ],
        ],
        'htn' => [
            'title'       => 'DASH Diet (Hypertension)',
            'macro'       => '55% Carb · 18% Protein · 27% Fat',
            'key_points'  => [
                'Restrict sodium to <1500–2000 mg/day (< ¾ tsp salt); avoid added salt at table',
                'Increase potassium: bananas, sweet potato, tomatoes, beans, leafy greens',
                'Increase calcium: low-fat milk, curd, tofu',
                'Increase magnesium: nuts, seeds, whole grains, dark chocolate',
                'Limit red meat to ≤2 servings/week; prefer fish and poultry',
                'Avoid pickles, papads, sauces, processed/canned foods, salted snacks',
                'Limit alcohol strictly; avoid caffeine excess (max 1 cup tea/coffee)',
                '8–10 servings of fruits and vegetables per day',
            ],
            'avoid'       => ['Table salt & pickles','Papads & chutneys','Processed / canned foods','Red meat (excess)','Full-fat dairy','Alcohol','Salted snacks','Baking soda / soda drinks'],
            'include'     => ['Banana / Sweet potato / Tomato (potassium)','Low-fat milk & curd (calcium)','Nuts & seeds (magnesium)','Whole grains','Fish & skinless chicken','Garlic & onion','Olive oil','All fruits & vegetables'],
            'meal_plan'   => [
                'Early Morning (6–7 am)'  => '1 glass warm water; 4 walnuts + 2 almonds',
                'Breakfast (8–9 am)'      => '2 whole-wheat bread slices / 2 multigrain dosa (no salt); 1 boiled egg or low-fat paneer; 1 banana; 1 cup low-fat milk',
                'Mid-Morning (11 am)'     => '1 fruit (apple / guava / pear); 1 cup lassi (no salt) or coconut water',
                'Lunch (1–2 pm)'          => '2 whole-wheat chapatis; 1 cup dal (low salt); 1 cup mixed vegetables; salad (no dressing); 1 cup low-fat curd',
                'Evening Snack (4–5 pm)'  => 'Roasted seeds (pumpkin / sunflower) or sprouts; 1 cup herbal tea',
                'Dinner (7–8 pm)'         => '2 chapatis or 1 cup rice; grilled/steamed fish or chicken; stir-fried vegetables (minimal oil & salt); soup',
            ],
        ],
        'cad' => [
            'title'       => 'Cardiac / CAD Diet',
            'macro'       => '50% Carb · 20% Protein · 30% Fat (mostly unsaturated)',
            'key_points'  => [
                'Limit saturated fat to <7% of total calories; eliminate trans fat entirely',
                'Target dietary cholesterol <200 mg/day; limit egg yolks to 3/week',
                'Increase omega-3 fatty acids: fatty fish (salmon, mackerel, sardines) 2×/week',
                'Use olive oil, rice bran oil, or mustard oil; avoid coconut oil, ghee, butter',
                'Eat 25–30 g of dietary fibre daily (oats, flaxseeds, psyllium husk)',
                'Restrict sodium to <2000 mg/day (same as HTN)',
                'Limit red meat; prefer fish, skinless chicken, low-fat dairy, pulses',
                'Alcohol: avoid completely',
            ],
            'avoid'       => ['Ghee / Butter / Cream','Coconut oil / Palm oil','Fried foods','Red & processed meats','Full-fat dairy','Trans fats (vanaspati, margarine)','Alcohol','High-sodium foods'],
            'include'     => ['Fatty fish (mackerel, sardines)','Flaxseeds / Chia seeds / Walnuts','Oats (beta-glucan)','Olive / Mustard oil','Fruits & vegetables','Whole pulses','Soy products','Dark leafy greens'],
            'meal_plan'   => [
                'Early Morning (6–7 am)'  => '1 glass warm water; 1 tsp flaxseed powder + 1 tsp psyllium husk in water',
                'Breakfast (8–9 am)'      => '1 cup oatmeal with 1 tbsp flaxseeds & berries; OR 2 egg-white omelette with 2 whole-wheat toast; 1 cup skimmed milk',
                'Mid-Morning (11 am)'     => '1 fruit (apple / pomegranate); 10 almonds or 4 walnuts',
                'Lunch (1–2 pm)'          => '2 whole-wheat chapatis; 1 cup dal; grilled fish or chicken (no frying); large salad; 1 cup low-fat curd',
                'Evening Snack (4–5 pm)'  => 'Sprouts chaat; 1 cup green tea',
                'Dinner (7–8 pm)'         => '1–2 chapatis; steamed / baked fish; stir-fried vegetables in olive oil; clear soup',
            ],
        ],
        'cholesterol' => [
            'title'       => 'Low Cholesterol / Heart-Healthy Diet',
            'macro'       => '55% Carb · 18% Protein · 27% Fat',
            'key_points'  => [
                'Reduce LDL cholesterol by cutting saturated and trans fats',
                'Eat 5–10 g soluble fibre/day: oats, beans, apples, psyllium',
                'Consume plant sterols/stanols (present in vegetable oils, nuts, seeds)',
                'Limit dietary cholesterol to <200 mg/day; max 3–4 egg yolks/week',
                'Replace red meat with fish, skinless chicken, tofu, pulses',
                'Use olive oil or rice bran oil for cooking (2–3 tsp/day)',
                'Eat a handful of nuts (almonds / walnuts) daily',
                'Avoid partially hydrogenated oils (trans fats)',
            ],
            'avoid'       => ['Ghee / Butter / Lard','Organ meats (liver, kidney)','Shell fish (prawns, crabs) in excess','Fried foods','Full-fat dairy','Coconut oil / Palm oil','Processed snacks','Bakery products'],
            'include'     => ['Oats / Barley (beta-glucan)','Almonds / Walnuts','Flaxseeds / Chia seeds','Apple / Citrus fruits (pectin)','Olive oil','Fish (omega-3)','Legumes / Beans','Garlic & onion'],
            'meal_plan'   => [
                'Early Morning (6–7 am)'  => '1 glass warm water with 1 tsp apple cider vinegar (optional); 5 almonds',
                'Breakfast (8–9 am)'      => '1 bowl oatmeal with walnuts and apple; OR 2 rye/multigrain toast + boiled egg white + fruit',
                'Mid-Morning (11 am)'     => '1 apple or citrus fruit; 1 cup green tea',
                'Lunch (1–2 pm)'          => '2 chapatis; 1 cup dal/rajma; stir-fried vegetables; cucumber–tomato salad; low-fat curd',
                'Evening Snack (4–5 pm)'  => '1 small bowl of sprouts; 4 walnuts; green tea',
                'Dinner (7–8 pm)'         => '2 chapatis or brown rice; baked/steamed fish; sautéed vegetables; clear soup',
            ],
        ],
        'uric_acid' => [
            'title'       => 'Low-Purine Diet (High Uric Acid / Gout)',
            'macro'       => '60% Carb · 15% Protein · 25% Fat',
            'key_points'  => [
                'Drink 3–4 litres of water daily to flush uric acid via kidneys',
                'Avoid ALL organ meats (liver, kidney, brain, sweetbreads)',
                'Avoid shellfish (prawns, crabs, lobster) and anchovies/sardines',
                'Limit red meat to 1 small serving, max 2×/week',
                'Avoid alcohol, especially beer (contains guanosine, a purine)',
                'Increase low-fat dairy (milk, curd) — helps lower serum uric acid',
                'Cherries and vitamin C (citrus) help lower uric acid',
                'Avoid fructose-sweetened drinks and fruit juices',
            ],
            'avoid'       => ['Organ meats (liver, kidney)','Red meat (excess)','Shellfish & anchovies','Beer & alcohol','Yeast extract / Brewer\'s yeast','Spinach / Asparagus / Mushrooms (excess)','Sugary drinks / Fruit juice','Refined carbs (maida, white sugar)'],
            'include'     => ['Water (3–4 L/day)','Low-fat milk & curd','Cherries & berries','Citrus fruits (Vitamin C)','Eggs & tofu','Whole grains','Vegetables (except high-purine)','Olive oil'],
            'meal_plan'   => [
                'Early Morning (6–7 am)'  => '2 glasses of water; 1 glass warm lemon water (Vit C)',
                'Breakfast (8–9 am)'      => '2 whole-wheat dosas or oatmeal or poha; 1 cup skimmed milk or low-fat curd; 1 small fruit (cherries / citrus)',
                'Mid-Morning (11 am)'     => '1 glass of water; 1 fruit (apple / pear / orange)',
                'Lunch (1–2 pm)'          => '2 chapatis or brown rice; 1 cup dal (low-purine pulses — moong, masoor); vegetables; low-fat curd',
                'Evening Snack (4–5 pm)'  => '1 glass of water; 1 cup low-fat buttermilk; handful of roasted seeds',
                'Dinner (7–8 pm)'         => '2 chapatis; paneer bhurji or egg-white preparation or tofu; stir-fried vegetables; 1 cup warm milk at bedtime',
            ],
        ],
        'hypothyroid' => [
            'title'       => 'Hypothyroid Diet',
            'macro'       => '50% Carb · 25% Protein · 25% Fat',
            'key_points'  => [
                'Ensure adequate iodine intake via iodized salt (but don\'t over-supplement)',
                'Selenium-rich foods help convert T4→T3: eggs, tuna, Brazil nuts, sunflower seeds',
                'Zinc: pumpkin seeds, chickpeas, cashews, lean meat',
                'Limit raw goitrogenic foods (cabbage, broccoli, cauliflower, kale, soy) — cooking reduces goitrogens',
                'Avoid calcium supplements or high-calcium foods within 4 hours of thyroid medication',
                'Increase fibre to combat constipation: whole grains, fruits, vegetables',
                'Limit processed and refined foods; control weight to reduce TSH',
                'Avoid crash diets; eat regular balanced meals',
            ],
            'avoid'       => ['Excess raw cruciferous vegetables (eat cooked)','Soy in excess','Millets in excess (goitrogen)','Very high-fibre around medication time','Processed & junk food','Refined sugar','Alcohol'],
            'include'     => ['Iodized salt','Eggs & fish','Selenium-rich foods (Brazil nuts, tuna)','Zinc: pumpkin seeds, chickpeas','Whole grains (fibre for constipation)','Fruits & vegetables (cooked)','Low-fat dairy','Adequate water'],
            'meal_plan'   => [
                'Early Morning (6–7 am)'  => '1 glass warm water; thyroid medication on empty stomach (wait 30–60 min before eating)',
                'Breakfast (8–9 am)'      => '2 whole-wheat rotis or oatmeal; 1–2 boiled eggs; 1 fruit; 1 cup skimmed milk (not soy milk)',
                'Mid-Morning (11 am)'     => '1 handful of mixed seeds (pumpkin, sunflower) or 2 Brazil nuts; 1 glass water',
                'Lunch (1–2 pm)'          => '2 chapatis or brown rice; 1 cup dal / rajma; cooked vegetables; salad; 1 cup curd',
                'Evening Snack (4–5 pm)'  => 'Boiled egg or sprouts; 1 cup green tea; handful of nuts',
                'Dinner (7–8 pm)'         => '2 chapatis; fish or chicken or paneer; cooked vegetables; warm soup',
            ],
        ],
        'ckd' => [
            'title'       => 'CKD (Renal) Diet',
            'macro'       => 'Based on GFR stage — generally 50–60% Carb · 10–15% Protein · 30% Fat',
            'key_points'  => [
                'Protein restriction based on GFR: Stage 3–4: 0.6–0.8 g/kg/day; Stage 5: 0.6 g/kg/day',
                'Restrict potassium: avoid bananas, oranges, tomatoes, potatoes, coconut water',
                'Restrict phosphorus: avoid dairy excess, nuts, beans, dark colas, processed foods',
                'Sodium restriction: <1500–2000 mg/day (similar to HTN)',
                'Fluid restriction as per urine output (only if oliguria present)',
                'Prefer high-biological-value proteins: eggs, fish, chicken (within limit)',
                'Soak vegetables in water before cooking to reduce potassium content',
                'Regular monitoring of serum electrolytes, creatinine, urea',
            ],
            'avoid'       => ['Bananas / Oranges / Coconut water (high K)','Tomatoes / Potatoes in excess','Dairy in excess (high P)','Nuts & seeds in excess (high P)','Dark colas','Salt & pickles','Red meat excess','Processed foods'],
            'include'     => ['Egg whites (high-quality protein)','Fish / Chicken (measured portion)','White rice / White bread (lower K&P than whole grain)','Apples / Berries (lower K)','Cabbage / Cauliflower / Beans (soaked & boiled)','Olive oil','Controlled water as prescribed'],
            'meal_plan'   => [
                'Early Morning (6–7 am)'  => '1 glass water (per allowed fluid); prescribed medications',
                'Breakfast (8–9 am)'      => '2 egg-white omelette; 2 slices white bread; apple; tea (weak, no milk excess)',
                'Mid-Morning (11 am)'     => 'A small portion of low-K fruit (apple, berries); water (as permitted)',
                'Lunch (1–2 pm)'          => '1 cup white rice; 1 cup dal (soaked & boiled — reduced K&P); stir-fried vegetables (soaked); egg white or small chicken portion',
                'Evening Snack (4–5 pm)'  => '2 plain crackers; 1 small apple; weak tea',
                'Dinner (7–8 pm)'         => '2 chapatis (white flour); vegetables (soaked, boiled, drained); small chicken/fish serving; controlled fluid',
            ],
        ],
        'obesity' => [
            'title'       => 'Weight Management / Obesity Diet',
            'macro'       => '45% Carb · 30% Protein · 25% Fat',
            'key_points'  => [
                'Create a calorie deficit of 500–750 kcal/day (0.5–0.75 kg loss/week)',
                'High-protein diet preserves muscle mass during weight loss',
                'High-fibre foods increase satiety and reduce total intake',
                'Eat mindfully; chew slowly; stop at 80% full',
                'Avoid skipping meals — it leads to overeating later',
                'Limit total fat, especially saturated fat; avoid trans fats',
                'Drink 2–3 glasses of water 30 minutes before meals',
                'Regular physical activity (150 min/week brisk walking minimum)',
            ],
            'avoid'       => ['Fried foods','Sugary drinks & juices','Ultra-processed snacks','Refined carbs (maida, white sugar)','Alcohol','Large portions','Eating after 8 pm','Skipping meals'],
            'include'     => ['High-fibre vegetables & salads','Whole pulses & legumes','Lean protein: egg whites, chicken breast, fish, tofu','Whole grains (small portions)','Fruits (not juice)','Water before meals','Green tea','Low-fat dairy'],
            'meal_plan'   => [
                'Early Morning (6–7 am)'  => '2 glasses of warm water; optional: 1 tsp apple cider vinegar; 5 soaked almonds',
                'Breakfast (8–9 am)'      => '2 egg whites + 1 whole egg scrambled with vegetables; OR oatmeal with seeds; 1 cup skimmed milk / green tea (no sugar)',
                'Mid-Morning (11 am)'     => '1 fruit (apple / guava / pear / orange); 1 glass water',
                'Lunch (1–2 pm)'          => '1 large bowl salad first; 2 small whole-wheat chapatis; 1 cup dal; cooked vegetables; 1 cup low-fat curd',
                'Evening Snack (4–5 pm)'  => '1 cup green tea; handful of roasted chana or sprouts; 1 glass water',
                'Dinner (7–8 pm)'         => '1–2 chapatis or 1 cup brown rice; grilled/baked fish or chicken; large portion of vegetables; clear soup',
            ],
        ],
    ];

    // BMI category helpers
    public static function bmiCategory(float $bmi): string {
        if ($bmi < 18.5) return 'Underweight';
        if ($bmi < 23.0) return 'Normal (Asian)';
        if ($bmi < 27.5) return 'Overweight (Asian)';
        return 'Obese (Asian)';
    }

    public static function bmiColor(float $bmi): string {
        if ($bmi < 18.5) return '#3b82f6';
        if ($bmi < 23.0) return '#22c55e';
        if ($bmi < 27.5) return '#f59e0b';
        return '#ef4444';
    }

    // Harris-Benedict calorie calculation
    public static function calculateCalories(
        float $heightCm, float $weightKg, int $age, string $gender,
        string $activity, float $bmi
    ): int {
        // BMR
        if (strtolower($gender) === 'female') {
            $bmr = 447.6 + (9.25 * $weightKg) + (3.10 * $heightCm) - (4.33 * $age);
        } else {
            $bmr = 88.36 + (13.4 * $weightKg) + (4.8 * $heightCm) - (5.7 * $age);
        }
        $factor = self::ACTIVITY_LEVELS[$activity]['factor'] ?? 1.2;
        $tdee = $bmr * $factor;

        // Deficit based on BMI
        if ($bmi < 18.5)      $adjustment = +300;   // Underweight: add calories
        elseif ($bmi < 23.0)  $adjustment = 0;       // Normal: maintain
        elseif ($bmi < 27.5)  $adjustment = -500;    // Overweight: mild deficit
        elseif ($bmi < 32.0)  $adjustment = -700;    // Obese I
        else                  $adjustment = -1000;   // Obese II+

        return (int) max(1200, round(($tdee + $adjustment) / 50) * 50);
    }
}
