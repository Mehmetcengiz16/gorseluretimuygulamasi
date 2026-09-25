<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bakım modunu ve engellenmiş hesapları mobil API'de uygular. */
class EnsureAppAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('app.maintenance') && ! $request->is('api/v1/app/config')) {
            throw new ApiException('Uygulama kısa bir bakımda, lütfen biraz sonra tekrar dene.', 'MAINTENANCE', 503);
        }

        $user = $request->user('sanctum');
        if ($user && ! $user->is_active) {
            $user->currentAccessToken()?->delete();
            throw ApiException::accountDisabled();
        }

        return $next($request);
    }
}
