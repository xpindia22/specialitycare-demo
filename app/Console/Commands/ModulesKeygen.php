<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * One-time setup: generate an ed25519 keypair for signing module licenses.
 *
 * Run this LOCALLY on the owner's machine (not on a buyer's server).
 * Keep the printed private key OFF the production server.
 */
class ModulesKeygen extends Command
{
    protected $signature = 'modules:keygen
                            {--out= : Directory to write private.key + public.key (default: storage/license)}
                            {--force : Overwrite existing keys without prompting}';

    protected $description = 'Generate an ed25519 keypair for signing module licenses (run locally only).';

    public function handle(): int
    {
        $dir = $this->option('out') ?: storage_path('license');
        // 0750 so the web server group (e.g. www-data) can read license.json/.sig
        // but only the owner can write. Private key inside is locked down separately.
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            $this->error("Cannot create directory: $dir");
            return self::FAILURE;
        }
        @chmod($dir, 0750);

        $privPath = $dir . '/private.key';
        $pubPath  = $dir . '/public.key';

        if ((is_file($privPath) || is_file($pubPath)) && !$this->option('force')) {
            if (!$this->confirm("Keys already exist at $dir. Overwrite?", false)) {
                $this->warn('Aborted.');
                return self::FAILURE;
            }
        }

        $kp     = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($kp);
        $public = sodium_crypto_sign_publickey($kp);

        file_put_contents($privPath, base64_encode($secret));
        // 0640 + group www-data so the owner panel (running as the web user)
        // can read the key to sign new licenses. This is acceptable on the
        // OWNER's machine — anyone with shell on Robert's box already has
        // the keys regardless. NEVER copy this file to a buyer's server.
        @chmod($privPath, 0640);
        $webGroup = $this->detectWebGroup();
        if ($webGroup) @chgrp($privPath, $webGroup);

        file_put_contents($pubPath, base64_encode($public));
        @chmod($pubPath, 0644);

        $this->info("Keypair written to $dir");
        $this->newLine();
        $this->line('Add this to your .env on EVERY deployment (and commit nothing private):');
        $this->newLine();
        $this->line('MODULES_PUBLIC_KEY=' . base64_encode($public));
        $this->newLine();
        $this->warn('KEEP private.key OFF buyer servers and OUT of git. Anyone with it can sign licenses.');
        if ($webGroup) {
            $this->newLine();
            $this->line("Private key set to group '$webGroup' so the owner panel can sign via the web UI.");
        } else {
            $this->newLine();
            $this->line('Could not detect a web server group on this host.');
            $this->line("If signing via the web panel fails, run: chgrp www-data $privPath && chmod 640 $privPath");
        }

        return self::SUCCESS;
    }

    /**
     * Best-effort detection of the local web server group so we can grant
     * read access to the private key on the owner's workstation. Returns
     * null on hosts that don't appear to be running a web server.
     */
    private function detectWebGroup(): ?string
    {
        foreach (['www-data', 'apache', 'nginx', 'http'] as $g) {
            if (function_exists('posix_getgrnam') && @posix_getgrnam($g)) return $g;
        }
        return null;
    }
}
