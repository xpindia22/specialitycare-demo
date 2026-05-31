<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DrugInteraction extends Model
{
    protected $fillable = ['drug1_name', 'drug2_name', 'severity', 'description', 'management'];

    const SEVERITIES = [
        'contraindicated' => ['label' => 'Contraindicated', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fca5a5'],
        'major'           => ['label' => 'Major',           'color' => '#c2410c', 'bg' => '#fff7ed', 'border' => '#fed7aa'],
        'moderate'        => ['label' => 'Moderate',        'color' => '#b45309', 'bg' => '#fffbeb', 'border' => '#fcd34d'],
        'minor'           => ['label' => 'Minor',           'color' => '#065f46', 'bg' => '#f0fdf4', 'border' => '#86efac'],
    ];

    /*
     * Brand → generic map.
     * IMPORTANT: More specific (longer) keys MUST come before shorter ones that
     * are substrings of the same word (e.g. "norflox tz" before "norflox").
     * Combination drugs use " + " separator.
     */
    const BRAND_MAP = [
        // ── COMBINATION DRUGS (specific keys first) ────────────────────────
        // Fluoroquinolone + tinidazole combos
        'norflox tz'         => 'norfloxacin + tinidazole',
        'n-flox tz'          => 'norfloxacin + tinidazole',
        'nf-tz'              => 'norfloxacin + tinidazole',
        'ciplox tz'          => 'ciprofloxacin + tinidazole',
        'cip-tz'             => 'ciprofloxacin + tinidazole',
        'cifran tz'          => 'ciprofloxacin + tinidazole',
        'o2 tz'              => 'ofloxacin + tinidazole',
        'onoff tz'           => 'ofloxacin + tinidazole',

        // Penicillin combinations
        'augmentin'          => 'amoxicillin + clavulanate',
        'augmed'             => 'amoxicillin + clavulanate',
        'novaclav'           => 'amoxicillin + clavulanate',
        'markmentin'         => 'amoxicillin + clavulanate',
        'mox cv'             => 'amoxicillin + clavulanate',
        'amoxyclav'          => 'amoxicillin + clavulanate',

        // Analgesic combinations
        'ultracet'           => 'tramadol + paracetamol',
        'combiflam'          => 'ibuprofen + paracetamol',
        'ibugesic plus'      => 'ibuprofen + paracetamol',

        // Aceclofenac combos (Acemiz/Acenac = aceclofenac, NOT diclofenac)
        'acemiz plus'        => 'aceclofenac + paracetamol',
        'acenac mr'          => 'aceclofenac + paracetamol',
        'acemiz mr'          => 'aceclofenac + paracetamol',

        // Antidiabetic combinations (glimp g/m = glimepiride + metformin)
        'glimp g'            => 'glimepiride + metformin',
        'glimp m'            => 'glimepiride + metformin',
        'glimda m'           => 'glimepiride + metformin',
        'vildalent-m'        => 'vildagliptin + metformin',
        'janumet'            => 'sitagliptin + metformin',
        'jalra m'            => 'vildagliptin + metformin',

        // Inhaler combos
        'foracort'           => 'formoterol + budesonide',
        'seroflo'            => 'salmeterol + fluticasone',
        'symbicort'          => 'formoterol + budesonide',

        // PPI + prokinetic
        'pantosec d'         => 'pantoprazole + domperidone',
        'pantop d'           => 'pantoprazole + domperidone',
        'omez d'             => 'omeprazole + domperidone',

        // Antiallergy combos
        'minolast'           => 'montelukast + levocetirizine',
        'cetcop-l'           => 'levocetirizine',       // cetirizine/levocetirizine

        // Alpha blocker combos
        'tamsulix-d'         => 'tamsulosin + dutasteride',
        'dutas t'            => 'tamsulosin + dutasteride',

        // Iron/vitamin combos
        'cafol z'            => 'iron + folic acid',
        'fefol z'            => 'iron + folic acid',
        'cofol-z'            => 'iron + folic acid',
        'cofol z'            => 'iron + folic acid',
        'feronia xt'         => 'iron + folic acid',

        // ── SINGLE DRUGS ───────────────────────────────────────────────────
        // Anticoagulants
        'acitrom'            => 'acenocoumarol',
        'acenocoumarol'      => 'acenocoumarol',
        'sintrom'            => 'acenocoumarol',
        'nicotrom'           => 'acenocoumarol',
        'warf'               => 'warfarin',
        'coumadin'           => 'warfarin',

        // Antiplatelets
        'ecosprin'           => 'aspirin',
        'tescosprin'         => 'aspirin',
        'asprin'             => 'aspirin',
        'clopimark'          => 'clopidogrel',
        'clopilet'           => 'clopidogrel',
        'deplatt'            => 'clopidogrel',
        'plavix'             => 'clopidogrel',
        'clopid'             => 'clopidogrel',

        // NSAIDs / analgesics (single)
        'dolo'               => 'paracetamol',
        'crocin'             => 'paracetamol',
        'calpol'             => 'paracetamol',
        'paracip'            => 'paracetamol',
        'paramol'            => 'paracetamol',
        'paralent'           => 'paracetamol',
        'ibugesic'           => 'ibuprofen',
        'brufen'             => 'ibuprofen',
        'defnac'             => 'diclofenac',
        'voveran'            => 'diclofenac',
        'voltaren'           => 'diclofenac',
        'acemiz'             => 'aceclofenac',  // single acemiz = aceclofenac
        'acenac'             => 'aceclofenac',
        'sumo'               => 'diclofenac',
        'ketorolac'          => 'ketorolac',
        'kelac'              => 'ketorolac',
        'tramadol'           => 'tramadol',

        // Cardiac
        'cordarone'          => 'amiodarone',
        'amioject'           => 'amiodarone',
        'lanoxin'            => 'digoxin',
        'digoxin'            => 'digoxin',
        'lasix'              => 'furosemide',
        'torsip'             => 'torasemide',
        'sorbitrate'         => 'isosorbide',
        'nitrocontin'        => 'nitroglycerine',
        'nicardia'           => 'nifedipine',
        'diltiazem'          => 'diltiazem',
        'diltanol'           => 'diltiazem',
        'nimodipine'         => 'nimodipine',
        'nimodip'            => 'nimodipine',
        'labetalol'          => 'labetalol',
        'propanolol'         => 'propranolol',
        'atenol'             => 'atenolol',
        'tenormin'           => 'atenolol',
        'met xl'             => 'metoprolol',
        'metxl'              => 'metoprolol',
        'betaloc'            => 'metoprolol',
        'lopressor'          => 'metoprolol',
        'amlip'              => 'amlodipine',
        'amlo'               => 'amlodipine',
        'stamlo'             => 'amlodipine',
        'norvasc'            => 'amlodipine',
        'nicoline'           => 'amlodipine',
        'telma'              => 'telmisartan',
        'telgard'            => 'telmisartan',
        'telmilix'           => 'telmisartan',
        'flavedon'           => 'trimetazidine',

        // Statins
        'atorvas'            => 'atorvastatin',
        'lipivas'            => 'atorvastatin',
        'lipvas'             => 'atorvastatin',
        'atorvastatin'       => 'atorvastatin',
        'elivas'             => 'rosuvastatin',
        'simcard'            => 'simvastatin',
        'zocor'              => 'simvastatin',
        'fenomark'           => 'fenofibrate',

        // Antibiotics (single, after combo entries above)
        'mox'                => 'amoxicillin',
        'azithro'            => 'azithromycin',
        'azicip'             => 'azithromycin',
        'cip-tz'             => 'ciprofloxacin + tinidazole', // duplicate safety
        'norflox'            => 'norfloxacin',
        'n-flox'             => 'norfloxacin',
        'oframax'            => 'ceftriaxone',
        'cefforen'           => 'cefuroxime',
        'cefixime'           => 'cefixime',
        'stancef'            => 'cefixime',
        'metronidazole'      => 'metronidazole',
        'flagyl'             => 'metronidazole',
        'metrogyl'           => 'metronidazole',
        'levoflox'           => 'levofloxacin',
        'loflor'             => 'levofloxacin',
        'onoff'              => 'ofloxacin',
        'linezolid'          => 'linezolid',

        // Antifungals
        'fluka'              => 'fluconazole',
        'itracip'            => 'itraconazole',
        'itrosted'           => 'itraconazole',
        'ketomark'           => 'ketoconazole',

        // Antiepileptics
        'eptoin'             => 'phenytoin',
        'eptolix'            => 'phenytoin',
        'deriphyllin'        => 'theophylline',
        'deriphylline'       => 'theophylline',
        'levelix'            => 'levetiracetam',
        'tegretol'           => 'carbamazepine',

        // PPIs / antacids (single, after combo entries above)
        'pantop'             => 'pantoprazole',
        'pantoloc'           => 'pantoprazole',
        'pantosec'           => 'pantoprazole',
        'pazom'              => 'pantoprazole',
        'omez'               => 'omeprazole',
        'prilosec'           => 'omeprazole',
        'gellusil'           => 'antacid',
        'mucaine'            => 'antacid',

        // Antidiabetics (single, after combo entries above)
        'metformin'          => 'metformin',
        'okamet'             => 'metformin',
        'glycomet'           => 'metformin',
        'glucophage'         => 'metformin',
        'diamicron'          => 'gliclazide',
        'glymark'            => 'gliclazide',
        'glimda'             => 'glimepiride',
        'glimp'              => 'glimepiride',
        'janument'           => 'sitagliptin',
        'tesenta'            => 'linagliptin',
        'vildalent'          => 'vildagliptin',
        'voglibose'          => 'voglibose',

        // Corticosteroids
        'dexona'             => 'dexamethasone',
        'dexalab'            => 'dexamethasone',
        'hydrocort'          => 'hydrocortisone',
        'bisone'             => 'hydrocortisone',
        'prednisolone'       => 'prednisolone',
        'wysolone'           => 'prednisolone',
        'omnacortil'         => 'prednisolone',
        'deflazacort'        => 'deflazacort',

        // Thyroid
        'thyrolix'           => 'levothyroxine',

        // Diuretics / electrolytes
        'shelcal'            => 'calcium',
        'cipcal'             => 'calcium',

        // Antihistamines / misc
        'avil'               => 'pheniramine',
        'phenergan'          => 'promethazine',
        'phenargan'          => 'promethazine',
        'vomistop'           => 'domperidone',
        'emeset'             => 'ondansetron',
        'onvin'              => 'ondansetron',
        'levocet'            => 'levocetirizine',
        'stugeron'           => 'cinnarizine',
        'vertilix'           => 'betahistine',
        'mucizid'            => 'acetylcysteine',
        'spasmonil'          => 'drotaverine',
        'drotin'             => 'drotaverine',
        'tamsuix'            => 'tamsulosin',
        'tamsulin'           => 'tamsulosin',
        'ursolix'            => 'ursodeoxycholic acid',
        'udiliv'             => 'ursodeoxycholic acid',
        'pystig'             => 'pyridostigmine',

        // Psychiatry / CNS
        'alprax'             => 'alprazolam',
        'xanax'              => 'alprazolam',
        'trika'              => 'alprazolam',
        'calmpose'           => 'diazepam',
        'valium'             => 'diazepam',
        'haldol'             => 'haloperidol',
        'amitrilix'          => 'amitriptyline',

        // Gout
        'allopuric'          => 'allopurinol',
        'zyloric'            => 'allopurinol',
        'febuxostat'         => 'febuxostat',

        // Immunosuppressants
        'sulfasalaz'         => 'sulfasalazine',

        // Iron / vitamins
        'ferrolix'           => 'iron',
        'iron'               => 'iron',
        'zincovit'           => 'zinc',
        'zinolvita'          => 'zinc',
    ];

    public function getSeverityLabelAttribute(): string {
        return self::SEVERITIES[$this->severity]['label'] ?? ucfirst($this->severity);
    }

    // Expand a list of drug names to include resolved generic equivalents.
    // Combination drugs (e.g. "norfloxacin + tinidazole") are split into individual components.
    public static function resolveGenericNames(array $names): array
    {
        $resolved = [];
        foreach ($names as $name) {
            $n = strtolower(trim($name));
            if (!$n) continue;
            $resolved[] = $n;

            $generic = null;
            foreach (self::BRAND_MAP as $keyword => $g) {
                if (str_contains($n, $keyword) || str_contains($keyword, $n)) {
                    $generic = $g;
                    break;
                }
            }

            if ($generic) {
                $resolved[] = $generic;
                // Split combination: "norfloxacin + tinidazole" → add each component
                if (str_contains($generic, '+')) {
                    foreach (explode('+', $generic) as $component) {
                        $c = trim($component);
                        if ($c) $resolved[] = $c;
                    }
                }
            }
        }
        return array_values(array_unique($resolved));
    }

    // Check interactions among a list of drug names (brand or generic).
    public static function checkNames(array $names): Collection
    {
        if (count($names) < 1) return collect();

        // Resolve brand names → generics and expand combinations
        $expanded = self::resolveGenericNames($names);

        // Also expand any '+' combinations that were in the original input
        $extra = [];
        foreach ($expanded as $n) {
            if (str_contains($n, '+')) {
                foreach (explode('+', $n) as $c) {
                    $c = trim($c);
                    if ($c) $extra[] = $c;
                }
            }
        }
        $expanded = array_unique(array_merge($expanded, $extra));

        if (count($expanded) < 2) return collect();

        $found = collect();
        $allInteractions = self::all();

        foreach ($allInteractions as $interaction) {
            $d1 = strtolower($interaction->drug1_name);
            $d2 = strtolower($interaction->drug2_name);

            $matchA = false; $matchedNameA = '';
            $matchB = false; $matchedNameB = '';

            foreach ($expanded as $name) {
                if (!$matchA && (str_contains($name, $d1) || str_contains($d1, $name))) {
                    $matchA = true; $matchedNameA = $name;
                }
                if (!$matchB && (str_contains($name, $d2) || str_contains($d2, $name))) {
                    $matchB = true; $matchedNameB = $name;
                }
                if ($matchA && $matchB) break;
            }

            if ($matchA && $matchB && $matchedNameA !== $matchedNameB) {
                $interaction->matched_drug1 = $matchedNameA;
                $interaction->matched_drug2 = $matchedNameB;
                $found->push($interaction);
            }
        }

        $order = ['contraindicated' => 0, 'major' => 1, 'moderate' => 2, 'minor' => 3];
        return $found->sortBy(fn($i) => $order[$i->severity] ?? 9);
    }
}
