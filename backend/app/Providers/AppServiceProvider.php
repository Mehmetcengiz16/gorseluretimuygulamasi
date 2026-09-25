<?php

namespace App\Providers;

use App\Services\Ai\AiClient;
use App\Services\Ai\FakeAiClient;
use App\Services\Ai\OpenRouterClient;
use App\Support\AiConfig;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Her çözümlemede ayarlar yeniden okunur: admin panelden girilen anahtar kuyruk işçisini
        // yeniden başlatmadan etkili olur. Anahtar yoksa sahte istemciye düşülür.
        $this->app->bind(AiClient::class, fn () => AiConfig::isFake()
            ? new FakeAiClient
            : new OpenRouterClient((string) AiConfig::apiKey(), AiConfig::baseUrl()));
    }

    public function boot(): void
    {
        Carbon::setLocale('tr');
        Paginator::defaultView('admin.partials.pagination');

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('generations', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('enhance', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
    }
}
