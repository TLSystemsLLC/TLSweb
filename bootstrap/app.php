<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', \App\Http\Middleware\ForceJson::class);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            // Normalize 404s (including missing routes) to the same generic response
            return response()->json([
                'rc' => 99,
                'ok' => false,
                'error' => 'Invalid request.',
            ], 400);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            // Normalize 405s (wrong HTTP verb) too
            return response()->json([
                'rc' => 99,
                'ok' => false,
                'error' => 'Invalid request.',
            ], 400);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, $request) {
            return response()->json([
                'rc' => 99,
                'ok' => false,
                'error' => 'Invalid request.',
            ], 429);
        });

        // IMPORTANT: any generic/catch-all renderer must NOT override this
        // (leave your other renderers below)
    })->create();
