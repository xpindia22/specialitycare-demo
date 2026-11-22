<?php

if (!function_exists('setting')) {
    function setting(string $key, $default = null): ?string
    {
        try {
            $row = \Illuminate\Support\Facades\DB::table('settings')->where('key', $key)->first();
            return $row ? $row->value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('clinic_signature')) {
    /**
     * Clinic name + full address as one line, for WhatsApp/email sign-offs.
     * Falls back to just the name if no address is configured. Reads live
     * from settings so a future address change doesn't need a code deploy.
     */
    function clinic_signature(): string
    {
        $name = setting('clinic_name', config('app.name', 'James Heart Care'));
        $address = trim((string) setting('clinic_address', ''));
        $address = trim(str_replace(["\r\n", "\n"], ', ', $address));

        return $address !== '' ? "{$name}, {$address}" : $name;
    }
}
