<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            $retryAfter = max(1, (int) ($exception->getHeaders()['Retry-After'] ?? 60));
            $message = 'Terlalu banyak percobaan. Silakan coba lagi dalam '.$retryAfter.' detik.';
            $headers = ['Retry-After' => (string) $retryAfter];

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'retry_after' => $retryAfter,
                ], 429, $headers);
            }

            if ($request->routeIs('login.store')) {
                return redirect()->route('login')
                    ->with('throttle_message', $message)
                    ->with('throttle_retry_after', min($retryAfter, 60))
                    ->withHeaders($headers);
            }

            return response()->view('errors.429', [
                'message' => $message,
                'retryAfter' => $retryAfter,
            ], 429, $headers);
        });
    })->create();
