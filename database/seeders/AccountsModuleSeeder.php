<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Indian clinic default Chart of Accounts + GST rates + voucher types.
 *
 * Idempotent — re-running won't duplicate. Safe to invoke with:
 *   php artisan db:seed --class=AccountsModuleSeeder
 *
 * After seeding, the user can rename / add / disable ledgers from the
 * Accounts → Ledgers page. The is_system flag protects core ledgers
 * (Cash, CGST/SGST/IGST input+output, Sundry Debtors/Creditors) from
 * deletion since the voucher engine references them by code.
 */
class AccountsModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedGroups();
        $this->seedGstRates();
        $this->seedLedgers();
        $this->seedVoucherTypes();
    }

    private function seedSettings(): void
    {
        $defaults = [
            ['key' => 'clinic_gstin',           'value' => '',     'group' => 'accounts'],
            ['key' => 'clinic_state_code',      'value' => '35',   'group' => 'accounts'],   // 35 = A&N Islands
            ['key' => 'clinic_state_name',      'value' => 'Andaman and Nicobar Islands', 'group' => 'accounts'],
            ['key' => 'clinic_legal_name',      'value' => '',     'group' => 'accounts'],
            ['key' => 'fy_start_month',         'value' => '4',    'group' => 'accounts'],   // April
            ['key' => 'accounts_auto_post_bills', 'value' => '1',  'group' => 'accounts'],
            ['key' => 'accounts_auto_post_purchases', 'value' => '1', 'group' => 'accounts'],
        ];
        foreach ($defaults as $s) {
            DB::table('settings')->updateOrInsert(['key' => $s['key']], $s);
        }
    }

    private function seedGroups(): void
    {
        // Primary groups (Tally-style), then sub-groups via parent code lookup.
        $primary = [
            ['code' => 'CAP',  'name' => 'Capital Account',       'nature' => 'Liabilities', 'sort_order' => 10],
            ['code' => 'LOAN', 'name' => 'Loans (Liability)',     'nature' => 'Liabilities', 'sort_order' => 20],
            ['code' => 'CL',   'name' => 'Current Liabilities',   'nature' => 'Liabilities', 'sort_order' => 30],
            ['code' => 'FA',   'name' => 'Fixed Assets',          'nature' => 'Assets',      'sort_order' => 40],
            ['code' => 'INV',  'name' => 'Investments',           'nature' => 'Assets',      'sort_order' => 50],
            ['code' => 'CA',   'name' => 'Current Assets',        'nature' => 'Assets',      'sort_order' => 60],
            ['code' => 'LA',   'name' => 'Loans & Advances (Asset)','nature' => 'Assets',    'sort_order' => 70],
            ['code' => 'SALE', 'name' => 'Sales Accounts',        'nature' => 'Income',      'sort_order' => 80],
            ['code' => 'PUR',  'name' => 'Purchase Accounts',     'nature' => 'Expenses',    'sort_order' => 90, 'affects_gross_profit' => true],
            ['code' => 'DIE',  'name' => 'Direct Income',         'nature' => 'Income',      'sort_order' => 100, 'affects_gross_profit' => true],
            ['code' => 'DXP',  'name' => 'Direct Expenses',       'nature' => 'Expenses',    'sort_order' => 110, 'affects_gross_profit' => true],
            ['code' => 'IIE',  'name' => 'Indirect Income',       'nature' => 'Income',      'sort_order' => 120],
            ['code' => 'IXP',  'name' => 'Indirect Expenses',     'nature' => 'Expenses',    'sort_order' => 130],
            ['code' => 'SUSP', 'name' => 'Suspense Account',      'nature' => 'Liabilities', 'sort_order' => 140],
        ];
        foreach ($primary as $g) {
            DB::table('account_groups')->updateOrInsert(
                ['code' => $g['code']],
                array_merge($g, ['is_system' => 1, 'updated_at' => now(), 'created_at' => now()])
            );
        }

        // Sub-groups (parent_code → child rows)
        $sub = [
            ['code' => 'BANK', 'name' => 'Bank Accounts',     'parent' => 'CA',  'nature' => 'Assets',      'sort_order' => 61],
            ['code' => 'CASH', 'name' => 'Cash-in-Hand',      'parent' => 'CA',  'nature' => 'Assets',      'sort_order' => 62],
            ['code' => 'STK',  'name' => 'Stock-in-Hand',     'parent' => 'CA',  'nature' => 'Assets',      'sort_order' => 63],
            ['code' => 'SDR',  'name' => 'Sundry Debtors',    'parent' => 'CA',  'nature' => 'Assets',      'sort_order' => 64],
            ['code' => 'SCR',  'name' => 'Sundry Creditors',  'parent' => 'CL',  'nature' => 'Liabilities', 'sort_order' => 31],
            ['code' => 'DT',   'name' => 'Duties & Taxes',    'parent' => 'CL',  'nature' => 'Liabilities', 'sort_order' => 32],
            ['code' => 'PROV', 'name' => 'Provisions',        'parent' => 'CL',  'nature' => 'Liabilities', 'sort_order' => 33],
        ];
        foreach ($sub as $g) {
            $parent = DB::table('account_groups')->where('code', $g['parent'])->value('id');
            unset($g['parent']);
            $g['parent_id'] = $parent;
            $g['is_system'] = 1;
            $g['created_at'] = now();
            $g['updated_at'] = now();
            DB::table('account_groups')->updateOrInsert(['code' => $g['code']], $g);
        }
    }

    private function seedGstRates(): void
    {
        $rates = [
            ['name' => 'GST 0% (Exempt)', 'rate' => 0,     'cess_rate' => 0],
            ['name' => 'GST 5%',          'rate' => 5,     'cess_rate' => 0],
            ['name' => 'GST 12%',         'rate' => 12,    'cess_rate' => 0],
            ['name' => 'GST 18%',         'rate' => 18,    'cess_rate' => 0],
            ['name' => 'GST 28%',         'rate' => 28,    'cess_rate' => 0],
        ];
        foreach ($rates as $r) {
            DB::table('gst_rates')->updateOrInsert(
                ['rate' => $r['rate'], 'cess_rate' => $r['cess_rate']],
                array_merge($r, ['is_active' => 1, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    private function seedLedgers(): void
    {
        // Clinic-flavored Indian default. is_system=1 protects against deletion.
        $ledgers = [
            // Capital
            ['code' => 'CAP-OWN', 'name' => 'Owner\'s Capital A/c',       'group' => 'CAP',  'dr_cr' => 'Cr', 'is_system' => 1],
            ['code' => 'CAP-DRW', 'name' => 'Drawings',                    'group' => 'CAP',  'dr_cr' => 'Dr', 'is_system' => 1],

            // Cash & Bank (system — voucher engine looks these up by code)
            ['code' => 'CASH',     'name' => 'Cash',                       'group' => 'CASH', 'dr_cr' => 'Dr', 'is_system' => 1, 'party_type' => 'Cash'],
            ['code' => 'BANK-PRI', 'name' => 'Bank — Primary',             'group' => 'BANK', 'dr_cr' => 'Dr', 'is_system' => 0, 'party_type' => 'Bank'],

            // Sundry Debtors / Creditors umbrella ledgers (for cash patients
            // and unnamed suppliers; named parties become their own ledgers)
            ['code' => 'SDR-CASH', 'name' => 'Cash Patients',              'group' => 'SDR',  'dr_cr' => 'Dr', 'is_system' => 1, 'party_type' => 'Customer'],
            ['code' => 'SCR-GEN',  'name' => 'General Suppliers',          'group' => 'SCR',  'dr_cr' => 'Cr', 'is_system' => 1, 'party_type' => 'Supplier'],

            // Duties & Taxes — Output (sales)
            ['code' => 'TAX-OCGST', 'name' => 'Output CGST',               'group' => 'DT',   'dr_cr' => 'Cr', 'is_system' => 1, 'party_type' => 'Tax'],
            ['code' => 'TAX-OSGST', 'name' => 'Output SGST',               'group' => 'DT',   'dr_cr' => 'Cr', 'is_system' => 1, 'party_type' => 'Tax'],
            ['code' => 'TAX-OIGST', 'name' => 'Output IGST',               'group' => 'DT',   'dr_cr' => 'Cr', 'is_system' => 1, 'party_type' => 'Tax'],
            ['code' => 'TAX-OCESS', 'name' => 'Output Cess',               'group' => 'DT',   'dr_cr' => 'Cr', 'is_system' => 1, 'party_type' => 'Tax'],
            // Input (purchases)
            ['code' => 'TAX-ICGST', 'name' => 'Input CGST',                'group' => 'DT',   'dr_cr' => 'Dr', 'is_system' => 1, 'party_type' => 'Tax'],
            ['code' => 'TAX-ISGST', 'name' => 'Input SGST',                'group' => 'DT',   'dr_cr' => 'Dr', 'is_system' => 1, 'party_type' => 'Tax'],
            ['code' => 'TAX-IIGST', 'name' => 'Input IGST',                'group' => 'DT',   'dr_cr' => 'Dr', 'is_system' => 1, 'party_type' => 'Tax'],
            ['code' => 'TAX-ICESS', 'name' => 'Input Cess',                'group' => 'DT',   'dr_cr' => 'Dr', 'is_system' => 1, 'party_type' => 'Tax'],
            ['code' => 'TAX-TDS',   'name' => 'TDS Payable',               'group' => 'DT',   'dr_cr' => 'Cr', 'is_system' => 0, 'party_type' => 'Tax'],
            ['code' => 'TAX-RNDOFF','name' => 'Round Off',                 'group' => 'IIE',  'dr_cr' => 'Cr', 'is_system' => 1],

            // Stock
            ['code' => 'STK-PHARM', 'name' => 'Pharmacy Stock',            'group' => 'STK',  'dr_cr' => 'Dr', 'is_system' => 1],
            ['code' => 'STK-LAB',   'name' => 'Lab Consumables',           'group' => 'STK',  'dr_cr' => 'Dr', 'is_system' => 0],

            // Fixed Assets
            ['code' => 'FA-EQUIP',  'name' => 'Medical Equipment',         'group' => 'FA',   'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'FA-FURN',   'name' => 'Furniture & Fittings',      'group' => 'FA',   'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'FA-COMP',   'name' => 'Computer & Accessories',    'group' => 'FA',   'dr_cr' => 'Dr', 'is_system' => 0],

            // Sales (Income)
            ['code' => 'SALE-CONS', 'name' => 'Consultation Income',       'group' => 'SALE', 'dr_cr' => 'Cr', 'is_system' => 1],
            ['code' => 'SALE-PROC', 'name' => 'Procedure Income',          'group' => 'SALE', 'dr_cr' => 'Cr', 'is_system' => 0],
            ['code' => 'SALE-PHARM','name' => 'Pharmacy Sales',            'group' => 'SALE', 'dr_cr' => 'Cr', 'is_system' => 1],
            ['code' => 'SALE-LAB',  'name' => 'Lab Income',                'group' => 'SALE', 'dr_cr' => 'Cr', 'is_system' => 1],
            ['code' => 'SALE-RAD',  'name' => 'Radiology Income',          'group' => 'SALE', 'dr_cr' => 'Cr', 'is_system' => 0],
            ['code' => 'SALE-IPD',  'name' => 'IPD Room Charges',          'group' => 'SALE', 'dr_cr' => 'Cr', 'is_system' => 0],
            ['code' => 'SALE-VAC',  'name' => 'Vaccination Income',        'group' => 'SALE', 'dr_cr' => 'Cr', 'is_system' => 0],
            ['code' => 'SALE-OTHER','name' => 'Other Income',              'group' => 'SALE', 'dr_cr' => 'Cr', 'is_system' => 1],

            // Purchases
            ['code' => 'PUR-PHARM', 'name' => 'Pharmacy Purchase',         'group' => 'PUR',  'dr_cr' => 'Dr', 'is_system' => 1],
            ['code' => 'PUR-LAB',   'name' => 'Lab Consumables Purchase',  'group' => 'PUR',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'PUR-SUPP',  'name' => 'Medical Supplies Purchase', 'group' => 'PUR',  'dr_cr' => 'Dr', 'is_system' => 0],

            // Indirect Expenses — the "insensible" overheads Robert flagged
            ['code' => 'EXP-RENT',  'name' => 'Rent',                      'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 1],
            ['code' => 'EXP-ELEC',  'name' => 'Electricity',               'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 1],
            ['code' => 'EXP-WATER', 'name' => 'Water Charges',             'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-MAID',  'name' => 'Maid / House Help Salary',  'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-CLEAN', 'name' => 'Cleaning & Housekeeping',   'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-PETROL','name' => 'Petrol & Travel',           'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-PHONE', 'name' => 'Telephone & Internet',      'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-OFF',   'name' => 'Office Expenses',           'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-REPAIR','name' => 'Repairs & Maintenance',     'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-SAL',   'name' => 'Staff Salaries',            'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 1],
            ['code' => 'EXP-PRINT', 'name' => 'Printing & Stationery',     'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-BANK',  'name' => 'Bank Charges',              'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-PROF',  'name' => 'Professional Fees (CA/Lawyer)','group' => 'IXP','dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-INS',   'name' => 'Insurance Premium',         'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-GSTLF', 'name' => 'GST Late Fees & Penalties', 'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-DEPR',  'name' => 'Depreciation',              'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 0],
            ['code' => 'EXP-MISC',  'name' => 'Miscellaneous Expenses',    'group' => 'IXP',  'dr_cr' => 'Dr', 'is_system' => 1],

            // Indirect Income
            ['code' => 'INC-INT',   'name' => 'Interest Received',         'group' => 'IIE',  'dr_cr' => 'Cr', 'is_system' => 0],
            ['code' => 'INC-DISC',  'name' => 'Discount Received',         'group' => 'IIE',  'dr_cr' => 'Cr', 'is_system' => 0],

            // Suspense (for unrouted entries)
            ['code' => 'SUSP-GEN',  'name' => 'Suspense A/c',              'group' => 'SUSP', 'dr_cr' => 'Cr', 'is_system' => 1],
        ];

        $groupIds = DB::table('account_groups')->pluck('id', 'code')->all();

        foreach ($ledgers as $l) {
            $row = [
                'name'                   => $l['name'],
                'code'                   => $l['code'],
                'group_id'               => $groupIds[$l['group']] ?? null,
                'opening_balance'        => 0,
                'opening_balance_dr_cr'  => $l['dr_cr'],
                'party_type'             => $l['party_type'] ?? null,
                'is_system'              => $l['is_system'] ?? 0,
                'is_active'              => 1,
                'created_at'             => now(),
                'updated_at'             => now(),
            ];
            DB::table('account_ledgers')->updateOrInsert(['code' => $l['code']], $row);
        }
    }

    private function seedVoucherTypes(): void
    {
        $types = [
            ['code' => 'PAY', 'name' => 'Payment',       'numbering_prefix' => 'PAY', 'affects' => 'Cash', 'sort_order' => 10],
            ['code' => 'RCT', 'name' => 'Receipt',       'numbering_prefix' => 'RCT', 'affects' => 'Cash', 'sort_order' => 20],
            ['code' => 'JV',  'name' => 'Journal',       'numbering_prefix' => 'JV',  'affects' => 'None', 'sort_order' => 30],
            ['code' => 'CTR', 'name' => 'Contra',        'numbering_prefix' => 'CTR', 'affects' => 'Both', 'sort_order' => 40],
            ['code' => 'SAL', 'name' => 'Sales',         'numbering_prefix' => 'SAL', 'affects' => 'None', 'sort_order' => 50],
            ['code' => 'PUR', 'name' => 'Purchase',      'numbering_prefix' => 'PUR', 'affects' => 'None', 'sort_order' => 60],
            ['code' => 'CN',  'name' => 'Credit Note',   'numbering_prefix' => 'CN',  'affects' => 'None', 'sort_order' => 70],
            ['code' => 'DN',  'name' => 'Debit Note',    'numbering_prefix' => 'DN',  'affects' => 'None', 'sort_order' => 80],
        ];
        foreach ($types as $v) {
            DB::table('voucher_types')->updateOrInsert(
                ['code' => $v['code']],
                array_merge($v, ['is_system' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
