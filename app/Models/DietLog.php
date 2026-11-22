<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A food-intake entry with its calorie value. Patient-scoped — no encounter.
 */
class DietLog extends Model
{
    protected $fillable = ['patient_id', 'logged_at', 'meal', 'food', 'quantity', 'calories', 'notes', 'recorded_by'];

    protected $casts = [
        'logged_at' => 'datetime',
        'calories'  => 'integer',
    ];

    public const MEALS = ['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner', 'snack' => 'Snack'];

    /**
     * Built-in calorie reference for common (India-leaning) foods, grouped for
     * the dropdown. Value = kcal for the stated serving. Buyer-added foods
     * persist as RxOption(field=food_item).
     */
    public const FOODS = [
        'Grains & breads' => [
            'Roti / Chapati (1)'        => 70,
            'Aalu Paratha (1)'          => 200,
            'Plain Paratha (1)'         => 180,
            'Rice, cooked (1 cup)'      => 200,
            'Idli (1)'                  => 58,
            'Dosa, plain (1)'           => 130,
            'Dosa, masala (1)'          => 210,
            'Bread slice (1)'           => 70,
            'Bread, 2 slices'           => 140,
            'Bread Omelette (2 slices)' => 280,
            'Poha (1 cup)'              => 180,
            'Upma (1 cup)'              => 200,
            'Puri (1)'                  => 110,
            'Bhatura (1)'               => 220,
            'Naan (1)'                  => 260,
            'Jeera rice (1 cup)'        => 220,
            'Biryani, chicken (1 plate)' => 480,
            'Biryani, veg (1 plate)'    => 350,
            'Fried rice (1 cup)'        => 250,
            'Uttapam (1)'               => 170,
        ],
        'Dals, curries & sabzi' => [
            'Dal (1 cup)'               => 150,
            'Dal fry (1 cup)'           => 180,
            'Rajma (1 cup)'             => 230,
            'Chole (1 cup)'             => 240,
            'Mixed veg sabzi (1 cup)'   => 150,
            'Aalu sabzi (1 cup)'        => 190,
            'Palak paneer (1 cup)'      => 280,
            'Sambar (1 cup)'            => 140,
            'Paneer curry (1 cup)'      => 300,
            'Matar paneer (1 cup)'      => 310,
            'Bhindi sabzi (1 cup)'      => 120,
            'Baingan bharta (1 cup)'    => 130,
            'Rasam (1 cup)'             => 50,
            'Coconut chutney (2 tbsp)'  => 80,
        ],
        'Non-veg' => [
            'Egg, boiled (1)'           => 78,
            'Egg, half fry (1)'         => 90,
            'Omelette, plain (2 eggs)'  => 180,
            'Omelette, masala (2 eggs)' => 210,
            'Chicken curry (1 cup)'     => 250,
            'Chicken biryani (1 plate)' => 480,
            'Chicken, grilled (100g)'   => 165,
            'Chicken, fried (100g)'     => 260,
            'Fish curry (1 cup)'        => 220,
            'Fish, fried (1 piece)'     => 190,
            'Mutton curry (1 cup)'      => 290,
            'Prawn curry (1 cup)'       => 200,
            'Egg curry (1 cup)'         => 220,
            'Tandoori chicken (100g)'   => 150,
        ],
        'Dairy & drinks' => [
            'Milk (1 cup)'              => 120,
            'Milk, skimmed (1 cup)'     => 80,
            'Curd (1 cup)'              => 100,
            'Curd, low-fat (1 cup)'     => 60,
            'Whey protein (1 scoop)'    => 120,
            'Paneer (100g)'             => 265,
            'Butter (1 tsp)'            => 35,
            'Butter (1 tbsp)'           => 102,
            'Cheese slice (1)'          => 70,
            'Tea with sugar (1 cup)'    => 60,
            'Tea, milk no sugar (1 cup)' => 25,
            'Tea, no sugar (1 cup)'     => 10,
            'Coffee with sugar (1 cup)' => 70,
            'Coffee, black (1 cup)'     => 5,
            'Buttermilk (1 glass)'      => 40,
            'Lassi, sweet (1 glass)'    => 200,
            'Lassi, salted (1 glass)'   => 80,
            'Soft drink (1 can)'        => 140,
            'Coconut water (1 glass)'   => 50,
            'Fruit juice (1 glass)'     => 110,
        ],
        'Fruits' => [
            'Banana (1)'                => 105,
            'Apple (1)'                 => 95,
            'Orange (1)'               => 62,
            'Mango (1 cup)'             => 100,
            'Papaya (1 cup)'            => 55,
            'Grapes (1 cup)'            => 104,
            'Watermelon (1 cup)'        => 46,
            'Pomegranate (1 cup)'       => 83,
            'Guava (1)'                 => 68,
            'Chikoo / Sapota (1)'       => 140,
        ],
        'Snacks & sweets' => [
            'Samosa (1)'                => 260,
            'Pakora (4 pcs)'            => 200,
            'Vada (1)'                  => 150,
            'Medu vada (1)'             => 140,
            'Biscuit (1)'               => 50,
            'Marie biscuit (1)'         => 30,
            'Banana cake (1 slice)'     => 210,
            'Gulab jamun (1)'           => 150,
            'Ladoo, besan (1)'          => 180,
            'Halwa (1 cup)'             => 300,
            'Kheer (1 cup)'             => 260,
            'Jalebi (2 pcs)'            => 150,
            'Almonds (10)'              => 70,
            'Cashews (10)'              => 90,
            'Peanuts (handful)'         => 160,
            'Popcorn (1 cup)'           => 55,
            'Chips (small pack)'        => 130,
        ],
        'Rice dishes' => [
            'Idli sambar (2 idli)'      => 250,
            'Pongal (1 cup)'            => 280,
            'Curd rice (1 cup)'         => 220,
            'Lemon rice (1 cup)'        => 240,
            'Tamarind rice (1 cup)'     => 250,
            'Khichdi (1 cup)'           => 230,
            'Pulao (1 cup)'             => 250,
        ],
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
}
