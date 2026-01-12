<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VendorScopeMiddleware;
use App\Http\Middleware\CorsMiddleware;

return Application::configure(basePath: dirname(__DIR__))
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)


->withMiddleware(function (Middleware $middleware): void {

    // Add CORS middleware first to handle all requests
    $middleware->prepend(CorsMiddleware::class);

    $middleware->api();

    $middleware->alias([
        'role' => RoleMiddleware::class,
        'vendor.scope' => VendorScopeMiddleware::class,
    ]);

})

    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication errors
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please provide a valid authentication token.',
                    'error' => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        // Handle validation errors
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed. Please check your input.',
                    'error' => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle model not found errors
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "{$model} not found.",
                    'error' => 'NOT_FOUND',
                ], 404);
            }
        });

        // Handle authorization errors
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'You do not have permission to perform this action.',
                    'error' => 'FORBIDDEN',
                ], 403);
            }
        });

        // Handle not found errors (404)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested resource was not found.',
                    'error' => 'NOT_FOUND',
                ], 404);
            }
        });

        // Handle method not allowed errors (405)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The HTTP method is not allowed for this endpoint.',
                    'error' => 'METHOD_NOT_ALLOWED',
                ], 405);
            }
        });

        // Handle all other exceptions
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                $response = [
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An error occurred while processing your request.',
                    'error' => 'INTERNAL_ERROR',
                ];

                // Include error details in development
                if (config('app.debug')) {
                    $response['debug'] = [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ];
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
