<?php

namespace App\Console\Commands;

use App\Models\ConsentTemplate;
use Illuminate\Console\Command;

/**
 * Seeds a set of common clinical consent templates. Idempotent — matches on
 * name, so re-running never duplicates and never overwrites edited bodies.
 * Bodies support placeholders: {patient_name} {jhc_id} {age} {gender}
 * {procedure} {date}.
 */
class SeedConsentTemplates extends Command
{
    protected $signature = 'consent:seed-templates';
    protected $description = 'Create common clinical consent templates (idempotent)';

    public function handle(): int
    {
        $sigBlock = <<<'HTML'
<br><br>
<p>I confirm that the nature, purpose, benefits, risks and alternatives (including the option of no treatment) have been explained to me in a language I understand, and my questions have been answered to my satisfaction. I give this consent voluntarily.</p>
<table style="width:100%;margin-top:24px">
  <tr>
    <td style="width:50%;padding-top:28px">Signature / Thumb impression of Patient / Guardian<br>Name: ____________________  Relation: __________</td>
    <td style="width:50%;padding-top:28px">Signature of Doctor<br>Name: ____________________</td>
  </tr>
  <tr>
    <td style="padding-top:16px">Witness: ____________________</td>
    <td style="padding-top:16px">Date &amp; Time: {date}</td>
  </tr>
</table>
HTML;

        $templates = [
            [
                'name' => 'General Consent for Treatment & Admission',
                'category' => 'general',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, hereby give my consent to be admitted and to undergo such examination, investigations and routine medical treatment as the treating doctors consider necessary during my stay.</p><p>I understand that medicine is not an exact science and that no guarantee has been given to me about the outcome of the treatment.</p>" . $sigBlock,
            ],
            [
                'name' => 'Surgical / Operation Consent',
                'category' => 'surgical',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, hereby authorise the treating surgeon and team to perform the following procedure: <strong>{procedure}</strong>, along with any additional or alternative procedure that may become necessary during the course of the operation.</p><p>The nature of the operation, the expected benefits, and the possible risks and complications (including bleeding, infection, anaesthetic complications and, in rare cases, organ injury or death) have been explained to me.</p>" . $sigBlock,
            ],
            [
                'name' => 'Anaesthesia Consent',
                'category' => 'anaesthesia',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, consent to the administration of anaesthesia (general / regional / local / sedation as appropriate) for the procedure: <strong>{procedure}</strong>.</p><p>The anaesthetist has explained the type of anaesthesia planned and its risks, including but not limited to nausea, sore throat, allergic reaction, breathing or cardiac complications and, rarely, serious adverse events.</p>" . $sigBlock,
            ],
            [
                'name' => 'Blood Transfusion Consent',
                'category' => 'blood',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, consent to the transfusion of blood and/or blood products as advised by my treating doctor.</p><p>I have been informed of the reason for the transfusion and its potential risks, including febrile / allergic reactions and the very small risk of transfusion-transmitted infection despite screening.</p>" . $sigBlock,
            ],
            [
                'name' => 'High-Risk Consent',
                'category' => 'surgical',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, have been informed that my condition and/or the proposed procedure <strong>{procedure}</strong> carries a HIGH RISK due to my present clinical status.</p><p>The treating team has explained that, despite all reasonable care, the outcome may be unfavourable and may include serious complications or death. I give my consent to proceed with full understanding of these risks.</p>" . $sigBlock,
            ],
            [
                'name' => 'HIV / Serology Testing Consent',
                'category' => 'investigation',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, consent to testing of my blood sample for HIV / Hepatitis B / Hepatitis C / other serological markers as advised.</p><p>I understand the purpose of the test, that my results will be kept confidential, and that pre- and post-test counselling is available to me.</p>" . $sigBlock,
            ],
            [
                'name' => 'Discharge Against Medical Advice (DAMA / LAMA)',
                'category' => 'general',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, am leaving the hospital / discontinuing treatment <strong>against the advice</strong> of the treating doctors, on my own responsibility.</p><p>The risks of leaving at this stage — which may include worsening of my condition, complications or death — have been clearly explained to me. I absolve the hospital and the treating team of responsibility for any consequences arising from this decision.</p>" . $sigBlock,
            ],
            [
                'name' => 'Procedure / Endoscopy Consent',
                'category' => 'surgical',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, consent to undergo the following diagnostic / therapeutic procedure: <strong>{procedure}</strong>.</p><p>The procedure, its purpose and its risks (including discomfort, bleeding, perforation and reaction to sedation) have been explained to me, along with available alternatives.</p>" . $sigBlock,
            ],
            [
                'name' => 'Contrast / Radiology Investigation Consent',
                'category' => 'investigation',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, consent to the radiological investigation <strong>{procedure}</strong>, including the administration of contrast medium if required.</p><p>I have disclosed any known allergies, kidney problems, pregnancy or prior contrast reaction. The risk of allergic or other reaction to contrast has been explained to me.</p>" . $sigBlock,
            ],
            [
                'name' => 'Photography / Clinical Media Consent',
                'category' => 'general',
                'body_html' => "<p>I, <strong>{patient_name}</strong> (JHC ID: {jhc_id}), aged {age}, {gender}, consent to clinical photography / video recording for the purpose of my medical record, treatment planning and, where applicable, anonymised teaching or publication.</p>" . $sigBlock,
            ],
        ];

        $created = 0; $skipped = 0;
        foreach ($templates as $t) {
            $existing = ConsentTemplate::where('name', $t['name'])->first();
            if ($existing) { $skipped++; $this->line("  skip (exists): {$t['name']}"); continue; }
            ConsentTemplate::create($t + ['is_active' => true]);
            $created++; $this->info("  created: {$t['name']}");
        }
        $this->newLine();
        $this->info("Done. Created {$created}, skipped {$skipped} (already present).");
        return self::SUCCESS;
    }
}
