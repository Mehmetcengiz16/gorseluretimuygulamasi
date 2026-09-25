<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Ai\AiRequestFailed;
use App\Services\Ai\OpenRouterClient;
use App\Support\AiConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    /** Form alanları: anahtar => [etiket, tür, sekme, yardım] */
    public const FIELDS = [
        'ai.api_key' => ['OpenRouter API anahtarı', 'secret', 'ai', 'openrouter.ai/keys adresinden alınır. Şifreli saklanır; boş bırakırsan mevcut anahtar korunur.'],
        'ai.fake_mode' => ['Sahte mod (test)', 'checkbox', 'ai', 'Açıkken OpenRouter\'a istek atılmaz, kredi/maliyet oluşmadan örnek görseller üretilir.'],
        'ai.image_model' => ['Görsel üretim modeli', 'image_model', 'ai', 'Stüdyo çekimi ve düzenleme araçları bu modelle üretilir.'],
        'ai.cutout_model' => ['Dekupe modeli', 'image_model', 'ai', 'Arka plan temizleme için daha ucuz bir model yeterlidir.'],
        'ai.text_model' => ['Prompt geliştirme modeli', 'text_model', 'ai', '"Prompt\'u Geliştir" butonu bu metin modelini kullanır.'],
        'ai.model_label' => ['Uygulamada görünen model adı', 'text', 'ai', 'Sonuçlar ekranındaki çipte gösterilir.'],
        'ai.daily_budget_usd' => ['Günlük AI bütçesi (USD)', 'number', 'ai', '0 = sınırsız. Aşılırsa yeni üretimler durdurulur.'],
        'ai.system_prompt' => ['Sistem prompt\'u', 'textarea', 'ai', 'Her üretimde modele gönderilen temel kurallar. Boş bırakırsan varsayılana döner.'],
        'ai.cutout_prompt' => ['Dekupe prompt\'u', 'textarea', 'ai', null],
        'credits.signup_bonus' => ['Kayıt bonusu (kredi)', 'number', 'credits', null],
        'credits.cutout_cost' => ['Dekupe ücreti (kredi)', 'number', 'credits', null],
        'credits.enhance_cost' => ['Prompt geliştirme ücreti (kredi)', 'number', 'credits', null],
        'limits.max_variants_free' => ['Ücretsiz kullanıcı en fazla varyant', 'number', 'limits', 'PRO olmayanlar için üst sınır (1/2/4/8).'],
        'app.maintenance' => ['Bakım modu', 'checkbox', 'app', 'Açıkken mobil API 503 döner.'],
        'app.min_version' => ['Minimum uygulama sürümü', 'text', 'app', null],
    ];

    public const TABS = ['ai' => 'Yapay Zekâ', 'credits' => 'Kredi', 'limits' => 'Limitler', 'app' => 'Uygulama'];

    /** Model alanları için öneri listesi (serbest metin de girilebilir). */
    public const MODEL_SUGGESTIONS = [
        'image_model' => [
            'google/gemini-3.1-flash-lite-image' => 'Google Nano Banana 2 Lite (önerilen, en ekonomik)',
            'google/gemini-2.5-flash-image' => 'Google Nano Banana (ekonomik)',
            'openai/gpt-5.4-image-2' => 'OpenAI GPT-5.4 Image 2 (en yüksek kalite, pahalı)',
            'openai/gpt-5-image' => 'OpenAI GPT-5 Image',
            'openai/gpt-5-image-mini' => 'OpenAI GPT-5 Image Mini (ekonomik)',
            'google/gemini-3-pro-image' => 'Google Gemini 3 Pro Image',
            'google/gemini-3.1-flash-image' => 'Google Gemini 3.1 Flash Image',
        ],
        'text_model' => [
            'openai/gpt-5.4-nano' => 'OpenAI GPT-5.4 Nano (önerilen, en ucuz)',
            'openai/gpt-5.4-mini' => 'OpenAI GPT-5.4 Mini',
            'openai/gpt-5.4' => 'OpenAI GPT-5.4',
        ],
    ];

    public function edit(): View
    {
        $values = collect(self::FIELDS)->mapWithKeys(fn ($f, $key) => [$key => match ($key) {
            'ai.api_key' => null, // anahtar asla forma geri basılmaz
            'ai.fake_mode' => AiConfig::fakeModeSetting(),
            default => Setting::get($key),
        }]);

        return view('admin.settings', [
            'fields' => self::FIELDS,
            'tabs' => self::TABS,
            'values' => $values,
            'suggestions' => self::MODEL_SUGGESTIONS,
            'aiMode' => AiConfig::isFake() ? 'fake' : 'live',
            'maskedKey' => AiConfig::maskedKey(),
            'keySource' => AiConfig::hasStoredKey() ? 'panel' : (filled(config('services.openrouter.key')) ? 'env' : null),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user('admin')->isSuperAdmin(), 403);

        $request->validate([
            'ai_api_key' => ['nullable', 'string', 'min:20', 'max:300', 'regex:/^\S+$/'],
        ], ['ai_api_key.regex' => 'API anahtarı boşluk içeremez.', 'ai_api_key.min' => 'API anahtarı çok kısa görünüyor.']);

        foreach (self::FIELDS as $key => [$label, $type]) {
            $input = str_replace('.', '_', $key);

            if ($type === 'secret') {
                if ($request->boolean($input.'_clear')) {
                    AiConfig::storeApiKey(null);
                } elseif ($request->filled($input)) {
                    AiConfig::storeApiKey($request->input($input));
                }

                continue;
            }

            $value = match ($type) {
                'checkbox' => $request->boolean($input),
                'number' => (float) $request->input($input, 0),
                // Boş metin alanı varsayılana döner (Setting::DEFAULTS).
                default => filled($request->input($input)) ? trim((string) $request->input($input)) : null,
            };
            if ($type === 'number' && floor($value) == $value) {
                $value = (int) $value;
            }
            Setting::put($key, $value);
        }

        return back()->with('success', 'Ayarlar kaydedildi. '.(AiConfig::isFake() ? 'Yapay zekâ sahte modda.' : 'Yapay zekâ canlı modda.'));
    }

    /**
     * Anahtarı gerçek bir OpenRouter çağrısıyla doğrular (sahte mod açık olsa bile) ve
     * seçili model kimliklerinin OpenRouter'da var olup olmadığını kontrol eder.
     */
    public function testConnection(): RedirectResponse
    {
        $key = AiConfig::apiKey();
        if (blank($key)) {
            return back()->with('error', 'Önce bir OpenRouter API anahtarı girip kaydet.');
        }

        try {
            $client = new OpenRouterClient($key, AiConfig::baseUrl());
            $reply = $client->generateText((string) Setting::get('ai.text_model'), 'Reply with a single word.', 'Say OK.', ['purpose' => 'enhance_prompt']);
        } catch (AiRequestFailed $e) {
            return back()->with('error', 'Bağlantı başarısız: '.$e->getMessage());
        }

        $missing = [];
        try {
            $available = collect(Http::timeout(20)->get(AiConfig::baseUrl().'/models')->json('data'))->pluck('id');
            foreach (['ai.image_model', 'ai.cutout_model', 'ai.text_model'] as $setting) {
                $model = (string) Setting::get($setting);
                if (! $available->contains($model)) {
                    $missing[] = $model;
                }
            }
        } catch (Throwable) {
            // Model listesi alınamazsa yalnızca anahtar doğrulaması raporlanır.
        }

        if ($missing) {
            return back()->with('error', 'Anahtar çalışıyor ancak şu model(ler) OpenRouter\'da bulunamadı: '.implode(', ', $missing));
        }

        return back()->with('success', 'Bağlantı başarılı, anahtar ve modeller geçerli. Model yanıtı: '.mb_substr($reply, 0, 60)
            .(AiConfig::isFake() ? ' — Canlıya geçmek için "Sahte mod"u kapatıp kaydet.' : ''));
    }
}
