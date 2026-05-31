<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Sign a module license with the owner's private key, then drop the
 * resulting license.json + license.sig into storage/license/ so the
 * deployed app can verify them.
 *
 * Intended to be run by the OWNER — either locally (then upload the
 * two files to the buyer's server) or on the buyer's server in a one-off
 * way if the private key has been temporarily placed there.
 */
class ModulesSign extends Command
{
    protected $signature = 'modules:sign
                            {--licensee= : Name of the buyer / institution}
                            {--modules= : Comma-separated module keys to enable (e.g. hr,opd)}
                            {--expires= : Expiry date YYYY-MM-DD (optional)}
                            {--key= : Path to ed25519 private key file (default: storage/license/private.key or MODULES_PRIVATE_KEY_PATH env)}
                            {--out= : Output directory (default: storage/license)}';

    protected $description = 'Sign a module license file with the ed25519 private key.';

    public function handle(\App\Services\LicenseSigner $signer): int
    {
        $licensee = (string) ($this->option('licensee') ?: $this->ask('Licensee (institution name)'));
        $modulesRaw = (string) ($this->option('modules') ?: $this->ask('Modules to enable (comma-separated, e.g. hr,opd)'));
        $modules = array_values(array_filter(array_map('trim', explode(',', $modulesRaw)), fn ($k) => $k !== ''));
        $expires = $this->option('expires') ? Carbon::parse($this->option('expires')) : null;
        $keyPath = $this->option('key') ?: null;

        try {
            $signed = $signer->sign($licensee, $modules, $expires, $keyPath);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $outDir = $this->option('out') ?: storage_path('license');
        try {
            if ($outDir !== dirname((string) config('modules.license_path'))) {
                // Custom output dir — write there, don't touch the live install.
                $signer->writePair($outDir.'/license.json', $outDir.'/license.sig',
                    $signed['json'], $signed['signature_b64']);
            } else {
                $signer->installLocally($signed['json'], $signed['signature_b64']);
            }
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('License signed.');
        $this->table(['Field', 'Value'], [
            ['Licensee',   $signed['payload']['licensee']],
            ['Modules',    implode(', ', $signed['payload']['modules'])],
            ['Issued',     $signed['payload']['issued_at']],
            ['Expires',    $signed['payload']['expires_at'] ?? 'never'],
            ['JSON',       $outDir . '/license.json'],
            ['Signature',  $outDir . '/license.sig'],
            ['Fingerprint', $signed['fingerprint']],
        ]);
        $this->newLine();
        $this->line('Upload BOTH files to the same path on the buyer\'s server.');

        return self::SUCCESS;
    }
}
