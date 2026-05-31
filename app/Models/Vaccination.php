<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    protected $fillable = [
        'patient_id', 'vaccine_code', 'vaccine_name', 'dose_label',
        'given_date', 'site', 'batch_number', 'manufacturer', 'expiry_date',
        'notes', 'given_by',
    ];

    protected $casts = [
        'given_date'  => 'date',
        'expiry_date' => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function givenBy() { return $this->belongsTo(\App\Models\User::class, 'given_by'); }

    /**
     * National Immunisation Schedule (India / IAP-aligned). Each entry is a
     * dose with a recommended-age window in days from birth. Order is the
     * canonical sequence the UI displays.
     */
    public const SCHEDULE = [
        ['code' => 'BCG',          'name' => 'BCG',                              'dose' => 'Birth',     'min_days' => 0,    'rec_days' => 0,    'max_days' => 365],
        ['code' => 'HEPB_BIRTH',   'name' => 'Hepatitis B',                      'dose' => 'Birth',     'min_days' => 0,    'rec_days' => 0,    'max_days' => 30],
        ['code' => 'OPV0',         'name' => 'OPV',                              'dose' => '0 (Birth)', 'min_days' => 0,    'rec_days' => 0,    'max_days' => 15],
        ['code' => 'PENTA1',       'name' => 'Pentavalent (DPT-HepB-Hib)',       'dose' => '1st',       'min_days' => 42,   'rec_days' => 42,   'max_days' => 365],
        ['code' => 'OPV1',         'name' => 'OPV',                              'dose' => '1st',       'min_days' => 42,   'rec_days' => 42,   'max_days' => 365],
        ['code' => 'IPV1',         'name' => 'IPV (fIPV)',                       'dose' => '1st',       'min_days' => 42,   'rec_days' => 42,   'max_days' => 365],
        ['code' => 'ROTA1',        'name' => 'Rotavirus',                        'dose' => '1st',       'min_days' => 42,   'rec_days' => 42,   'max_days' => 240],
        ['code' => 'PCV1',         'name' => 'Pneumococcal (PCV)',               'dose' => '1st',       'min_days' => 42,   'rec_days' => 42,   'max_days' => 365],
        ['code' => 'PENTA2',       'name' => 'Pentavalent (DPT-HepB-Hib)',       'dose' => '2nd',       'min_days' => 70,   'rec_days' => 70,   'max_days' => 365],
        ['code' => 'OPV2',         'name' => 'OPV',                              'dose' => '2nd',       'min_days' => 70,   'rec_days' => 70,   'max_days' => 365],
        ['code' => 'ROTA2',        'name' => 'Rotavirus',                        'dose' => '2nd',       'min_days' => 70,   'rec_days' => 70,   'max_days' => 240],
        ['code' => 'PENTA3',       'name' => 'Pentavalent (DPT-HepB-Hib)',       'dose' => '3rd',       'min_days' => 98,   'rec_days' => 98,   'max_days' => 365],
        ['code' => 'OPV3',         'name' => 'OPV',                              'dose' => '3rd',       'min_days' => 98,   'rec_days' => 98,   'max_days' => 365],
        ['code' => 'IPV2',         'name' => 'IPV (fIPV)',                       'dose' => '2nd',       'min_days' => 98,   'rec_days' => 98,   'max_days' => 365],
        ['code' => 'PCV2',         'name' => 'Pneumococcal (PCV)',               'dose' => '2nd',       'min_days' => 98,   'rec_days' => 98,   'max_days' => 365],
        ['code' => 'ROTA3',        'name' => 'Rotavirus',                        'dose' => '3rd',       'min_days' => 98,   'rec_days' => 98,   'max_days' => 240],
        ['code' => 'MR1',          'name' => 'Measles-Rubella (MR)',             'dose' => '1st',       'min_days' => 273,  'rec_days' => 273,  'max_days' => 730],
        ['code' => 'JE1',          'name' => 'Japanese Encephalitis',            'dose' => '1st',       'min_days' => 273,  'rec_days' => 273,  'max_days' => 730],
        ['code' => 'PCV_BOOSTER',  'name' => 'Pneumococcal (PCV)',               'dose' => 'Booster',   'min_days' => 273,  'rec_days' => 273,  'max_days' => 730],
        ['code' => 'VITA',         'name' => 'Vitamin A',                        'dose' => '1st',       'min_days' => 273,  'rec_days' => 273,  'max_days' => 1825],
        ['code' => 'DPT_B1',       'name' => 'DPT Booster',                      'dose' => '1st',       'min_days' => 480,  'rec_days' => 540,  'max_days' => 730],
        ['code' => 'OPV_B',        'name' => 'OPV Booster',                      'dose' => 'Booster',   'min_days' => 480,  'rec_days' => 540,  'max_days' => 730],
        ['code' => 'MR2',          'name' => 'Measles-Rubella (MR)',             'dose' => '2nd',       'min_days' => 480,  'rec_days' => 540,  'max_days' => 730],
        ['code' => 'JE2',          'name' => 'Japanese Encephalitis',            'dose' => '2nd',       'min_days' => 480,  'rec_days' => 540,  'max_days' => 730],
        ['code' => 'DPT_B2',       'name' => 'DPT Booster',                      'dose' => '2nd',       'min_days' => 1825, 'rec_days' => 1825, 'max_days' => 2190],
        ['code' => 'TD_10Y',       'name' => 'Td (Tetanus + Diphtheria)',        'dose' => '10y',       'min_days' => 3650, 'rec_days' => 3650, 'max_days' => 4380],
        ['code' => 'TD_16Y',       'name' => 'Td (Tetanus + Diphtheria)',        'dose' => '16y',       'min_days' => 5840, 'rec_days' => 5840, 'max_days' => 6570],
        ['code' => 'HPV1',         'name' => 'HPV',                              'dose' => '1st',       'min_days' => 3285, 'rec_days' => 3285, 'max_days' => 5475],
        ['code' => 'HPV2',         'name' => 'HPV',                              'dose' => '2nd',       'min_days' => 3470, 'rec_days' => 3470, 'max_days' => 5475],
    ];
}
