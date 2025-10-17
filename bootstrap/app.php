<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; // Importa la clase
use Illuminate\Http\Request; // Importa la clase

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckUserRole::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // --- INICIO DE LA MODIFICACIÓN ---
        // Aquí manejamos las excepciones para renderizar páginas de error personalizadas.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            // Si la petición espera una respuesta JSON (por ejemplo, desde una API),
            // devolvemos un error JSON estándar.
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Not Found.'], 404);
            }

            // Para cualquier otra petición (web), renderizamos nuestro componente
            // de Vue 'NotFound' usando Inertia.
            // Asegúrate de que la ruta al componente sea correcta.
            // El '404' al final establece el código de estado HTTP correcto.
            return inertia('404PageNotFound', [], 404);
        });
    })->create();
