<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aliases para usar los middlewares por nombre corto en las rutas:
        //   ->middleware('role:admin,empleado')
        //   ->middleware('internal-token')
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'internal-token' => \App\Http\Middleware\VerifyInternalToken::class,
        ]);

        // Las rutas internas con token son stateless: sin sesión ni CSRF.
        $csrfExceptions = ['api/internal/*'];

        // Cuando se ejecutan los tests con PHPUnit, los Feature tests
        // hacen $this->post(...) sin pasar token CSRF (en Laravel 11+
        // ya no se desactiva solo). Detectamos PHPUnit por su binario
        // en argv y añadimos un wildcard de excepción solo en ese caso.
        // NO afecta a producción ni al servidor Apache.
        $primerArg = $_SERVER['argv'][0] ?? '';
        if ($primerArg !== '' && str_contains($primerArg, 'phpunit')) {
            $csrfExceptions[] = '*';
        }

        $middleware->validateCsrfTokens(except: $csrfExceptions);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
