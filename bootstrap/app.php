<?php

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $forApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (ValidationException $e, Request $request) use ($forApi) {
            if ($forApi($request)) {
                return ApiResponse::fail($e->validator->errors()->first(), 422);
            }

            return null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($forApi) {
            if ($forApi($request)) {
                return ApiResponse::fail('Not found.', 404);
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($forApi) {
            if ($forApi($request)) {
                return ApiResponse::fail('Please login to continue.', 401);
            }

            return null;
        });
    })->create();
