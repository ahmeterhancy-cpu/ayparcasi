<?php

use App\Http\Middleware\MaintenanceMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tiko'nun bildirimi bizim formumuzdan doğmuyor, CSRF jetonu
        // taşıyamaz; kendi imzasıyla (Hash) doğrulanıyor. iFrame dönüş
        // yolu ise middleware'i tamamen bırakıyor — bkz. routes/web.php.
        $middleware->validateCsrfTokens(except: [
            'odeme/bildirim',   // sunucudan sunucuya JSON bildirim
        ]);

        // "Yapım aşamasında" perdesi yalnız vitrini kapatır. Filament paneli
        // kendi middleware yığınını kurduğundan yönetim tarafı hep açık kalır.
        $middleware->appendToGroup('web', MaintenanceMode::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
