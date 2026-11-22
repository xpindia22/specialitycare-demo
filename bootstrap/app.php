<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'                 => \App\Http\Middleware\AdminOnly::class,
            'role'                  => \App\Http\Middleware\RoleMiddleware::class,
            'permission'            => \App\Http\Middleware\PermissionMiddleware::class,
            'module'                => \App\Http\Middleware\EnsureModuleEnabled::class,
            'owner'                 => \App\Http\Middleware\EnsureOwner::class,
            'restrict.admin.owner'  => \App\Http\Middleware\RestrictAdminToOwner::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureUserIsActive::class);

        // phpBB SSO bridge — sets the cross-app login cookie on every web
        // response. Appended after StartSession so Auth::check() works.
        $middleware->appendToGroup('web', \App\Http\Middleware\ForumSso::class);

        // The SSO cookie must be delivered RAW (phpBB can't run Laravel's
        // cookie cipher), so exclude it from cookie encryption.
        $middleware->encryptCookies(except: [
            'sc_forum_sso',
        ]);

        // Unauthenticated patients hitting the portal go to the portal login,
        // not the staff login.
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('portal') || $request->is('portal/*')) {
                return route('portal.login');
            }
            return route('login');
        });

        // Heartbeat receiver is hit by buyer installs without a session, so
        // exclude it from CSRF.
        $middleware->validateCsrfTokens(except: [
            'api/license/heartbeat',
            'webhooks/whatsapp/inbound',
            'webhooks/whatsapp/inbound/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
