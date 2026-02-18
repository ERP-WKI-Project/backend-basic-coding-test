<?php

use App\Exceptions\BusinessRuleException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Illuminate\Http\Request;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: null,
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return ApiResponse::error(
                'Unauthenticated',
                [
                    'auth' => 'error.unauthenticated',
                ],
                401
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'Route not found',
                    [
                        'route' => 'error.not_found',
                    ],
                    404
                );
            }
        });

        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            return ApiResponse::error(
                $e->getMessage(),
                [
                    $e->field => $e->errorCode,
                ],
                400
            );
        });
    })->create();
