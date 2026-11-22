<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * One-off broadcast: notify every active patient of the clinic's new address
 * via WhatsApp, in English, Hindi, or Tamil (--lang, default en). Each
 * language is tracked as its own `kind` (address_change[_hi|_ta]) with its
 * own [ref:...] tag, so the three languages are independent broadcasts to
 * the same patient list — running one language doesn't affect another, and
 * re-running the same language only reaches whoever hasn't received it yet
 * (e.g. after the shared WHATSAPP_DAILY_LIMIT cuts a run short).
 */
class SendAddressChangeWhatsApp extends Command
{
    protected $signature = 'patients:send-address-change-whatsapp
                            {--lang=en  : en, hi, or ta}
                            {--limit=   : max messages this run (safety cap)}
                            {--dry-run  : list who would be messaged, send nothing}';
    protected $description = 'WhatsApp the new clinic address to every active patient (one-off relocation notice)';

    private const REF_TAG = 'addr_change_2026';

    private const BODIES = [
        'en' => "Hi %s, we have relocated. %s is now located at: "
            . "c/o Issac Home, Next to Divyam Talkies Movie Theater, Prothrapur, Port Blair. "
            . "For assistance, please contact reception at 9242001215. Regards, %s team.",
        'hi' => "नमस्ते %s, हम स्थानांतरित हो गए हैं। %s अब यहाँ स्थित है: "
            . "c/o Issac Home, Next to Divyam Talkies Movie Theater, Prothrapur, Port Blair। "
            . "सहायता के लिए कृपया रिसेप्शन से 9242001215 पर संपर्क करें। सादर, %s टीम।",
        'ta' => "வணக்கம் %s, நாங்கள் இடம் மாறிவிட்டோம். %s இப்போது இங்கு அமைந்துள்ளது: "
            . "c/o Issac Home, Next to Divyam Talkies Movie Theater, Prothrapur, Port Blair. "
            . "உதவிக்கு தயவுசெய்து வரவேற்பு பிரிவை 9242001215 என்ற எண்ணில் தொடர்பு கொள்ளவும். "
            . "அன்புடன், %s குழு.",
    ];

    public function handle(WhatsAppService $wa): int
    {
        if (!$wa->isEnabled()) {
            $this->warn('WhatsApp disabled.');
            return self::SUCCESS;
        }

        $lang = strtolower((string) $this->option('lang'));
        if (!isset(self::BODIES[$lang])) {
            $this->error("Unknown --lang={$lang}. Use en, hi, or ta.");
            return self::FAILURE;
        }

        $templateKey = $lang === 'en' ? 'address_change' : "address_change_{$lang}";
        $kind        = $templateKey;
        $refPrefix   = $lang === 'en' ? self::REF_TAG : self::REF_TAG . "_{$lang}";

        $clinic = setting('clinic_name', config('app.name', 'James Heart Care'));
        $limit  = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');

        $patients = Patient::where('is_active', true)
            ->whereNotNull('phone')
            ->get();

        $sent = $skipped = $alreadyDone = 0;
        foreach ($patients as $p) {
            if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); break; }

            $ref = "{$refPrefix}_{$p->id}";
            $already = WhatsAppMessage::where('kind', $kind)
                ->where('status', 'sent')
                ->where('body', 'like', "%[ref:{$ref}]%")->exists();
            if ($already) { $alreadyDone++; continue; }

            $name = $p->full_name ?: 'there';
            $body = sprintf(self::BODIES[$lang], $name, $clinic, $clinic) . " [ref:{$ref}]";

            if ($dryRun) {
                $sent++;
                $this->line("DRY-RUN [{$lang}] would notify {$p->full_name} ({$p->phone})");
                continue;
            }

            // Template {$templateKey}: {{1}} name.
            $r = $wa->sendTemplateToPatient($p, $templateKey, [$name], $body, $kind);
            if ($r['ok'] ?? false) { $sent++; }
            else { $skipped++; }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Address-change broadcast [{$lang}] — {$verb}: {$sent}, skipped: {$skipped}, already done: {$alreadyDone}.");
        return self::SUCCESS;
    }
}
