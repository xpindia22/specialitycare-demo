<?php
// FILE: app/Models/PatientAllergy.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAllergy extends Model
{
    protected $table = 'patient_allergies';

    protected $fillable = ['patient_id', 'allergen', 'severity'];

    public const COMMON_ALLERGENS = [
        'drugs' => [
            'Penicillin',
            'Amoxicillin',
            'Aspirin',
            'Ibuprofen',
            'NSAIDs',
            'Sulpha Drugs',
            'Codeine',
            'Morphine',
            'Tetracycline',
            'Erythromycin',
            'Cephalosporins',
            'ACE Inhibitors',
            'Iodine Contrast',
            'Local Anaesthetics',
            'Metronidazole',
            'Ciprofloxacin',
            'Vancomycin',
            'Carbamazepine',
            'Phenytoin',
            'Allopurinol',
        ],
        'foods' => [
            'Peanuts',
            'Tree Nuts',
            'Shellfish',
            'Eggs',
            'Milk',
            'Wheat',
            'Soy',
            'Fish',
            'Sesame',
        ],
        'environmental' => [
            'Pollen',
            'Dust Mites',
            'Pet Dander',
            'Mold',
            'Cockroach',
        ],
        'other' => [
            'Latex',
            'Bee Stings',
            'Nickel',
            'Alcohol',
            'Chlorhexidine',
        ],
    ];

    public static function allList(): array
    {
        $flat = [];
        foreach (self::COMMON_ALLERGENS as $items) {
            foreach ($items as $item) {
                $flat[] = $item;
            }
        }
        $flat = array_unique($flat);
        sort($flat);
        return array_values($flat);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
