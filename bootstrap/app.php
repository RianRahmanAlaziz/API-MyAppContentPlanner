<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 422 - Validation
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'error' => [
                        'type' => 'validation_error',
                        'details' => $e->errors(),
                    ],
                ], 422);
            }
        });

        // 403 - Forbidden (Policy / authorize)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'error' => [
                        'type' => 'forbidden',
                    ],
                ], 403);
            }
        });

        // 404 - Route model binding / Model not found
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not found',
                    'error' => [
                        'type' => 'not_found',
                    ],
                ], 404);
            }
        });

        // 401/404/429/etc - HTTP exceptions
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();

                $type = match ($status) {
                    401 => 'unauthenticated',
                    404 => 'not_found',
                    429 => 'rate_limited',
                    default => 'http_error',
                };

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Request failed',
                    'error' => [
                        'type' => $type,
                    ],
                ], $status);
            }
        });

        // 500 - Fallback
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server error',
                    'error' => [
                        'type' => 'server_error',
                        'details' => config('app.debug') ? $e->getMessage() : null,
                    ],
                ], 500);
            }
        });
    })->create();
