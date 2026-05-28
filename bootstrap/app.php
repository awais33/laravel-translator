<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // SubstituteBindings middleware converts ModelNotFoundException → NotFoundHttpException,
        // so we check the previous exception to produce a model-specific message.
        $exceptions->render(function (NotFoundHttpException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                $model = class_basename($previous->getModel());
                return ApiResponse::notFound("{$model} not found.");
            }
            return ApiResponse::notFound('Resource not found.');
        });

        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::error('Validation failed.', 422, $e->errors());
        });

        $exceptions->render(function (AuthenticationException $e) {
            return ApiResponse::unauthorized('Unauthenticated.');
        });
    })->create();
