<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Admin paneli dışındaki oturumsuz web istekleri admin girişine yönlenir.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request) => $request->is('api/*');

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json([
                    'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri.',
                    'code' => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json(['message' => 'Oturumun sona erdi, lütfen tekrar giriş yap.', 'code' => 'UNAUTHENTICATED', 'errors' => (object) []], 401);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json(['message' => 'Çok fazla istek gönderdin, biraz bekle.', 'code' => 'RATE_LIMITED', 'errors' => (object) []], 429);
            }
        });

        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json(['message' => 'Kayıt bulunamadı.', 'code' => 'NOT_FOUND', 'errors' => (object) []], 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json(['message' => $e->getMessage() ?: 'İstek işlenemedi.', 'code' => 'HTTP_'.$e->getStatusCode(), 'errors' => (object) []], $e->getStatusCode());
            }
        });
    })->create();
