<?php
namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route group behind a module key.
 *
 * Usage in routes:
 *   Route::middleware('module:hr')->group(...)
 *
 * Returns 404 (not 403) when the module is disabled, so a buyer probing
 * URLs cannot tell a disabled module from a non-existent route.
 */
class EnsureModuleEnabled
{
    public function __construct(private readonly LicenseService $license) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        if (!$this->license->isEnabled($moduleKey)) {
            // Friendly "this module isn't licensed for your install" page —
            // shows the module label/description from the catalog so the
            // buyer knows what to ask the vendor about. Catalog content is
            // already inspectable via the read-only Modules page so this
            // doesn't leak anything new.
            $catalog = (array) config('modules.catalog', []);
            $module = $catalog[$moduleKey] ?? ['label' => ucfirst($moduleKey), 'description' => '', 'icon' => 'fa-cube'];

            return response()->view('errors.module-not-licensed', [
                'moduleKey' => $moduleKey,
                'module'    => $module,
            ], 402); // 402 Payment Required — semantically accurate
        }
        return $next($request);
    }
}
