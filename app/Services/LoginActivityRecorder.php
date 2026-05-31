<?php

namespace App\Services;

use App\Models\LoginActivity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Records login / failed-login / logout events with the client IP and a
 * best-effort country lookup. All work is wrapped in try/catch so an auth
 * flow is never broken by logging or a slow geo API.
 */
class LoginActivityRecorder
{
    public function record(string $event, ?int $userId, ?string $email): void
    {
        if (!config('access.log_logins', true)) {
            return;
        }

        try {
            $ip = $this->clientIp();
            [$country, $city] = $this->geo($ip);

            LoginActivity::create([
                'user_id'    => $userId,
                'email'      => $email,
                'event'      => $event,
                'ip_address' => $ip,
                'country'    => $country,
                'city'       => $city,
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('LoginActivity record failed: ' . $e->getMessage());
        }
    }

    private function clientIp(): ?string
    {
        // Respect a reverse proxy (Apache on nvme sits in front of PHP).
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $h) {
            $v = request()->server($h);
            if ($v) {
                return trim(explode(',', $v)[0]);
            }
        }
        return request()->ip();
    }

    /** @return array{0:?string,1:?string} [country, city] */
    private function geo(?string $ip): array
    {
        if (!$ip || !config('access.geo_lookup', true)) {
            return [null, null];
        }
        // Skip private / loopback ranges — no geo for those.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['Local/Private', null];
        }

        try {
            $resp = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,city',
            ]);
            if ($resp->ok() && ($resp->json('status') === 'success')) {
                return [$resp->json('country'), $resp->json('city')];
            }
        } catch (\Throwable $e) {
            // network/timeout — leave null, never block login
        }
        return [null, null];
    }
}
