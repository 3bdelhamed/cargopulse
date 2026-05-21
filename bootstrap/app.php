<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainRuleException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['stripe/*',]);

        $middleware->api(append: [
            'throttle:tenant_api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : null;
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden.'], 403)
                : null;
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Resource not found.'], 404)
                : null;
        });

        $exceptions->render(function (ConflictException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 409)
                : null;
        });

        $exceptions->render(function (DomainRuleException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 400)
                : null;
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Database constraint violation.'], 409)
                : null;
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson() && $e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = match ($status) {
                    404 => 'Resource not found.',
                    405 => 'Method not allowed.',
                    429 => 'Too many requests.',
                    default => 'Request failed.',
                };

                return response()->json(['message' => $message], $status);
            }

            return $request->expectsJson()
                ? response()->json(['message' => 'Internal server error.'], 500)
                : null;
        });
    })->create();
