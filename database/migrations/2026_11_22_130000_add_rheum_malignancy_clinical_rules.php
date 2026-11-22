<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Additional clinical reminder rules: rheumatology / autoimmune / malignancy.
    // Additive + idempotent (insertOrIgnore on the unique name), so it is safe to
    // run everywhere regardless of whether rows also arrive via the specialitycare
    // DB sync from nvme.
    public function up(): void {
        if (!Schema::hasTable('clinical_rules')) return;

        $r = function (array $a) {
            return array_merge([
                'category' => 'symptom', 'severity' => 'info', 'match_type' => 'keyword',
                'metric' => null, 'comparator' => null, 'value_num' => null,
                'keywords' => null, 'keyword_fields' => null,
                'gender' => null, 'age_min' => null, 'age_max' => null,
                'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ], $a);
        };

        $rows = [
            $r(['name' => 'Rheumatoid arthritis', 'severity' => 'warning',
                'keywords' => 'rheumatoid,rheumatoid arthritis,morning stiffness,symmetrical joint,small joint pain,swollen joints,swelling of joints,swelling of hands,mcp joint,pip joint',
                'suggestion' => 'Suspected RA: order RF, anti-CCP, ESR, CRP and CBC; X-ray hands & feet; check LFT, renal profile and hepatitis B/C before DMARDs; refer rheumatology — early DMARD (methotrexate) prevents joint damage.']),
            $r(['name' => 'SLE / lupus', 'severity' => 'warning',
                'keywords' => 'lupus,systemic lupus,malar rash,butterfly rash,discoid rash',
                'suggestion' => 'Suspected SLE: order ANA, anti-dsDNA, complement C3/C4, CBC (cytopenias), ESR, urine routine (proteinuria/casts) and serum creatinine; add anti-phospholipid antibodies if clots or pregnancy loss; refer rheumatology.']),
            $r(['name' => 'Gout / high uric acid', 'category' => 'lab', 'severity' => 'warning',
                'keywords' => 'gout,podagra,hyperuricemia,hyperuricaemia,high uric acid,raised uric acid,big toe pain,first toe swelling,tophi',
                'suggestion' => 'Suspected gout/hyperuricaemia: check serum uric acid, renal profile, fasting glucose & lipids; aspirate joint for urate crystals if effusion; X-ray chronic joints. Acute: NSAID or colchicine — do NOT start or stop urate-lowering drugs during an attack. Advise low-purine diet and limit alcohol.']),
            $r(['name' => 'Scleroderma / systemic sclerosis', 'severity' => 'warning',
                'keywords' => 'scleroderma,systemic sclerosis,sclerodactyly,skin tightening,raynaud,digital ulcer',
                'suggestion' => 'Suspected scleroderma: order ANA, anti-Scl-70 and anti-centromere antibodies; monitor BP closely (scleroderma renal crisis); PFT with DLCO (ILD) and echo (pulmonary hypertension); nailfold capillaroscopy; refer rheumatology.']),
            $r(['name' => 'Autoimmune / connective-tissue disease', 'severity' => 'warning',
                'keywords' => 'autoimmune,connective tissue,vasculitis,sjogren,sjögren,polymyalgia,myositis,dermatomyositis,mixed connective tissue',
                'suggestion' => 'Suspected autoimmune/CTD: order ANA profile, ESR, CRP, CBC, RF and urine routine; add disease-specific antibodies per picture (anti-Ro/La, ANCA, anti-Jo1, CPK for myositis); refer rheumatology.']),
            $r(['name' => 'Inflammatory arthritis / polyarthralgia', 'severity' => 'info',
                'keywords' => 'arthritis,polyarthralgia,polyarthritis,joint pains,multiple joint pain,joint stiffness,painful joints',
                'suggestion' => 'Joint pain: characterise inflammatory vs mechanical and the pattern/number of joints; order ESR, CRP, CBC, RF, serum uric acid and X-ray of affected joints; if inflammatory add ANA/anti-CCP; refer if persistent or deforming.']),
            $r(['name' => 'Malignancy red flags', 'severity' => 'urgent',
                'keywords' => 'cancer,malignancy,carcinoma,tumour,tumor,lump,breast lump,neck lump,abdominal mass,palpable mass,lymphadenopathy,enlarged lymph node,unexplained bleeding,blood in stool,blood in urine,hemoptysis,haemoptysis,dysphagia,difficulty swallowing,night sweats',
                'suggestion' => 'Cancer red flags: do CBC and ESR; image the site (USG/X-ray/CT as appropriate) and biopsy/FNAC any persistent lump or node; chest X-ray; order tumour markers only when site-directed; arrange urgent specialist referral — do not watch-and-wait.']),
        ];

        $base = (int) (DB::table('clinical_rules')->max('sort_order') ?? 0);
        foreach ($rows as $i => $row) {
            $rows[$i]['sort_order'] = $base + 1 + $i;
        }
        DB::table('clinical_rules')->insertOrIgnore($rows);
    }

    public function down(): void {
        if (!Schema::hasTable('clinical_rules')) return;
        DB::table('clinical_rules')->whereIn('name', [
            'Rheumatoid arthritis', 'SLE / lupus', 'Gout / high uric acid',
            'Scleroderma / systemic sclerosis', 'Autoimmune / connective-tissue disease',
            'Inflammatory arthritis / polyarthralgia', 'Malignancy red flags',
        ])->delete();
    }
};
