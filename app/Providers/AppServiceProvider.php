<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Auth\Events\Login;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('helpers.php');

        $this->app->singleton(\App\Services\LicenseService::class);
    }

    public function boot(): void
    {
        // Buyer-managed license config. Shared-hosting buyers can't edit
        // .env, so the admin UI writes public_key + heartbeat_url to a
        // JSON file. Overlay those onto runtime config ONLY when the
        // matching .env values are empty — .env always wins on owner /
        // dev installs that set things the old way.
        $this->loadBuyerLicenseConfig();

        // Login activity log (IP + country) + failed/logout tracking.
        Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            app(\App\Services\LoginActivityRecorder::class)
                ->record('login', $event->user?->id, $event->user?->email);
        });
        Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            app(\App\Services\LoginActivityRecorder::class)
                ->record('failed', null, $event->credentials['email'] ?? null);
        });
        Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            app(\App\Services\LoginActivityRecorder::class)
                ->record('logout', $event->user?->id, $event->user?->email);
        });

        // Admin email notifications — fire when key domain models change
        \App\Models\Patient::observe(\App\Observers\PatientObserver::class);
        \App\Models\Encounter::observe(\App\Observers\EncounterObserver::class);
        \App\Models\Appointment::observe(\App\Observers\AppointmentObserver::class);
        \App\Models\Prescription::observe(\App\Observers\PrescriptionObserver::class);
        \App\Models\PrescriptionItem::observe(\App\Observers\PrescriptionItemObserver::class);
        \App\Models\Admission::observe(\App\Observers\AdmissionObserver::class);
        \App\Models\Bill::observe(\App\Observers\BillObserver::class);
        \App\Models\Diagnosis::observe(\App\Observers\DiagnosisObserver::class);
        \App\Models\LabOrder::observe(\App\Observers\LabOrderObserver::class);

        // Accounts module — auto-post Sales / Receipt vouchers from billing.
        // Both observers run their work via dispatch()->afterResponse() so
        // the billing UI is never blocked by accounting.
        \App\Models\Bill::observe(\App\Observers\BillVoucherObserver::class);
        \App\Models\Payment::observe(\App\Observers\PaymentVoucherObserver::class);

        // Auto-mark today's attendance for staff at first login of the day.
        // If the staff already has an attendance row for today (any status), don't touch it.
        Event::listen(Login::class, function (Login $event) {
            try {
                $user = $event->user;
                if (!$user) return;
                $staff = \App\Models\Staff::where('user_id', $user->id)->where('is_active', true)->first();
                if (!$staff) return;
                $existing = \App\Models\Attendance::where('staff_id', $staff->id)
                    ->whereDate('date', today())->first();
                if ($existing) return;
                \App\Models\Attendance::create([
                    'staff_id'  => $staff->id,
                    'date'      => today()->toDateString(),
                    'status'    => 'present',
                    'check_in'  => now()->format('H:i:s'),
                    'notes'     => 'Auto from login',
                    'marked_by' => $user->id,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Auto-checkin on login failed: ' . $e->getMessage());
            }
        });

        View::composer('*', function ($view) {
            if (auth()->check()) {
                try {
                    $view->with('__pendingLabs', DB::table('lab_orders')->whereIn('status', ['Ordered', 'Collected', 'Processing'])->count());
                    $view->with('__lowStock', DB::table('pharmacy_stock')->where('is_active', true)->whereColumn('quantity', '<=', 'min_stock_level')->where('quantity', '>', 0)->count());
                } catch (\Exception $e) {
                    $view->with('__pendingLabs', 0);
                    $view->with('__lowStock', 0);
                }
            }
        });
    }

    /**
     * Overlay storage/license/buyer_config.json values onto config('modules.*')
     * so the admin-UI-managed key and heartbeat URL work the same as if they
     * were in .env. Only fills in slots that .env left empty — .env wins.
     */
    private function loadBuyerLicenseConfig(): void
    {
        $path = (string) config('modules.buyer_config_path');
        if ($path === '' || !is_file($path)) return;

        $raw = @file_get_contents($path);
        if ($raw === false) return;
        $cfg = json_decode($raw, true);
        if (!is_array($cfg)) return;

        if (((string) config('modules.public_key', '')) === '' && !empty($cfg['public_key'])) {
            Config::set('modules.public_key', (string) $cfg['public_key']);
        }
        if (((string) config('modules.heartbeat_url', '')) === '' && !empty($cfg['heartbeat_url'])) {
            Config::set('modules.heartbeat_url', (string) $cfg['heartbeat_url']);
        }
    }
}
