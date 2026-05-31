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
