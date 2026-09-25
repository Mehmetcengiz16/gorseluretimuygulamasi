# StudioAI — Proje Dokümanı

> Kullanıcının telefonla çektiği sıradan ürün fotoğrafını, yapay zekâ ile **profesyonel stüdyo ortamında çekilmiş ticari ürün görseline** dönüştüren mobil uygulama.

| Katman | Teknoloji |
|---|---|
| Mobil uygulama | **Flutter** (iOS + Android) |
| Backend API | **Laravel 12** (PHP 8.2+), Laravel Sanctum |
| Admin panel | **Blade + özel CSS** (çerçevesiz, Obsidian Amber teması, `/admin`) |
| Veritabanı | **MySQL 8** |
| Görsel üretim | **OpenRouter API** (görsel üretebilen modeller) |
| Kuyruk | Laravel Queue — `database` sürücüsü |
| Dosya depolama | Laravel `public` disk (yerel) |

**Docker kullanılmaz.** Geliştirme ortamı: yerel PHP + Composer + MySQL (Laragon / XAMPP / yerel kurulum) ve Flutter SDK.

---

## İçindekiler

1. [Ürün Tanımı](#1-ürün-tanımı)
2. [Ekranlar](#2-ekranlar)
3. [İş Kuralları](#3-iş-kuralları)
4. [Sistem Mimarisi](#4-sistem-mimarisi)
5. [Veritabanı](#5-veritabanı)
6. [REST API](#6-rest-api)
7. [OpenRouter Entegrasyonu](#7-openrouter-entegrasyonu)
8. [Admin Panel](#8-admin-panel)
9. [Flutter Uygulaması](#9-flutter-uygulaması)
10. [Tasarım Sistemi → Flutter](#10-tasarım-sistemi--flutter)
11. [Kurulum (Docker'sız)](#11-kurulum-dockersız)
12. [Yol Haritası](#12-yol-haritası)
13. [Klasör Yapısı](#13-klasör-yapısı)

---

## 1. Ürün Tanımı

### 1.1 Amaç

E-ticaret satıcıları ve küçük markalar ürünlerini stüdyoda çektirmek için ciddi zaman ve para harcar. StudioAI; ürün fotoğrafını alır, ürünü arka plandan ayırır (**dekupe**), seçilen sahne / stil / ışık ayarlarına göre **ticari kalitede stüdyo görseli** üretir ve birden fazla varyasyon sunar.

**Temel ilke:** Ürünün kendisi (şekli, etiketi, rengi, logosu, yazıları) **değişmez**; yalnızca ortam, yüzey, ışık ve atmosfer değişir.

### 1.2 Hedef Kitle

| Persona | İhtiyaç |
|---|---|
| E-ticaret satıcısı (Trendyol, Shopify, Etsy) | Katalog için tutarlı, temiz ve lifestyle görseller |
| Küçük marka / butik üretici | Reklam ve sosyal medya kampanya görselleri |
| İçerik üreticisi | Hızlı, estetik, trend sahneler |

### 1.3 Ana Kullanıcı Akışı

```
[Keşfet] ──şablon "Kullan"──┐
                            ▼
[Fotoğraf Seç] → [1. Ürün & Dekupe] → [2. Sahne: Prompt/Stil/Işık/Kalite/Varyant] → [3. Render] → [Sonuçlar & Karşılaştırma]
                                                                                                      │
                                                                         İndir / Paylaş / Favori / +Varyant Üret
```

1. Kullanıcı kameradan/galeriden ürün fotoğrafı seçer (veya Keşfet'ten bir şablonla başlar → stil/ışık/prompt önceden dolu gelir).
2. Fotoğraf sunucuya yüklenir → **proje** oluşur → **AI Dekupe** çalışır.
3. Kullanıcı **Hızlı Sahne Türü** seçer, **Gölge & Yansıma** anahtarını açar/kapar.
4. **Stüdyo Ayarları**'nda prompt yazar (veya *Prompt'u Geliştir*), stil, ışık, kalite ve varyant sayısını seçer; harcanacak kredi anlık gösterilir.
5. *Stüdyo Çekimi Oluştur* → üretim kuyruğa alınır, uygulama durumu takip eder.
6. **Sonuçlar** ekranında önce/sonra kaydırmalı karşılaştırma, varyant geçişi, indirme, paylaşma, favorileme, ek varyant üretimi.

---

## 2. Ekranlar

Tasarımlar `ekrantasarimlari/` klasöründedir. Her klasörde `screen.png` (görsel) ve `code.html` (HTML/Tailwind referansı — ölçü, renk ve metinler buradan alınır).

### 2.1 Tasarımı hazır ekranlar

#### Keşfet / Şablonlar — `ekrantasarimlari/ke_fet_ablonlar`
- Üst bar: StudioAI logosu + "Keşfet", PRO rozeti, profil avatarı
- Arama kutusu ("Stüdyo stili veya ürün ara…") + filtre butonu
- Kategori çipleri: Tümü, Kozmetik, Parfüm & Cam, … (yatay kaydırma)
- "Popüler Stüdyo Setleri" — 2 kolonlu grid; kartta: kapak görseli, PRO/TREND rozeti, favori (kalp), beğeni sayısı, kategori etiketi, başlık, alt başlık, **Kullan →**
- Alt banner: "Tek Tıkla Yeni Ürün Çekimi" + **Başlat**
- Alt navigasyon

#### Ön Yükleme & Segmentasyon — `ekrantasarimlari/r_n_y_kleme_segmentasyon`
- Başlık "Photo Editor", geri, avatar
- 3 adımlı stepper: **1 Ürün & Dekupe → 2 Sahne → 3 Render**
- Dekupe önizleme (şeffaf zemin / dama deseni), "AI DEKUPE TAMAMLANDI" rozeti, katman ve yakınlaştırma butonları
- Araçlar: **Yeniden Kırp**, **Kenar Düzelt**, **Değiştir** (fotoğrafı değiştir)
- "Hızlı Sahne Türü" yatay liste (Podyum Üzeri, Havada Asılı, Doğal Taş & Su, …) — "5 STİL MEVCUT"
- "Gölge & Yansıma Ekle" anahtarı
- CTA: **Işık & Prompt Ayarlarına Geç →**

#### Stüdyo Ayarları & Prompt — `ekrantasarimlari/st_dyo_ayarlar_prompt`
- Başlık "Görsel Oluşturucu / Stüdyo Parametreleri & Ayarlar", **Hazır Set** butonu (şablon seçici)
- **Akıllı Prompt**: çok satırlı metin, sayaç `142/500`, **Temizle**, **Prompt'u Geliştir**
- **Stüdyo Stili** yatay liste: Yok/Ham, Lüks Ticari, Minimalist, Cyber Neon, Botanik… + Tümünü Gör
- **Stüdyo Işıklandırması** 2×2: Softbox (Yumuşak & Eşit), Spot Işık (Dramatik Kontrast), Altın Saat (Sıcak Güneş Halesi), Arkadan Işık (Rim Işıltısı & Ayrım)
- **Görsel Detay & Kalite** kaydırıcı: Taslak (Düşük) / Dengeli / Ultra 4K
- **Çekim Sayısı (Varyant)**: 1 / 2 / 4 / 8 — "4 Kredi Harcanacak"
- CTA: **Stüdyo Çekimi Oluştur** (PRO rozetli)

#### Sonuçlar & Karşılaştırma — `ekrantasarimlari/sonu_lar_kar_la_t_rma`
- Üst çipler: model adı, "Commercial Grade", "HDR 4K"
- **Prompt Özeti** + Kopyala
- **Canlı Karşılaştırma**: orijinal ↔ sonuç, sürüklenebilir ayırıcı; favori (yıldız) ve indir butonları
- Ürün adı, oran ve çözünürlük (örn. `4:5 Portre • 2160 × 2700 px`), **V1 MASTER** rozeti
- **Stüdyo Varyasyonları**: V1…V4 küçük resimler + **+4 Üret** — "4 / 4 Hazır"
- Düzenleme araç çubuğu (metin, sihirli değnek, ışık, kırp, renk, çerçeve) — Faz 2/3
- **Tümünü 4K HDR İndir**, **Shopify'a Aktar** (Faz 3), **Kataloğa Paylaş**

#### Tasarım sistemi — `ekrantasarimlari/obsidian_amber/DESIGN.md`
Renk, tipografi, boşluk, köşe ve bileşen kuralları. Flutter karşılıkları [Bölüm 10](#10-tasarım-sistemi--flutter)'da.

### 2.2 Tasarımı bekleyen ekranlar

| Ekran | Not |
|---|---|
| Splash / Onboarding | 2–3 sayfalık tanıtım |
| Giriş / Kayıt / Şifremi Unuttum | E-posta + şifre (ileride Google / Apple) |
| Projeler | Geçmiş projeler ve üretimler (grid) |
| Profil | Kredi bakiyesi, PRO durumu, kredi geçmişi, ayarlar, çıkış |
| Render bekleme | Sonuçlar ekranında iskelet (shimmer) + ilerleme ile çözülebilir |
| Kredi / PRO satın alma | Faz 3 |

Tasarım gelene kadar bu ekranlar Obsidian Amber'e uygun **geçici** ekranlarla yapılır.

### 2.3 Alt Navigasyon

Yüzen, buzlu cam efektli dock — 4 sekme: **Keşfet** · **Stüdyo** (yeni çekim) · **Projeler** · **Profil**. Seçili ikon amber renge döner.

---

## 3. İş Kuralları

### 3.1 Kredi
- Maliyet formülü: `kredi = varyant_sayısı × kalite_çarpanı` (tasarımdaki "4 varyant → 4 kredi" için varsayılan çarpanlar: Taslak 1, Dengeli 1, Ultra 4K 1; admin değiştirebilir).
- Kredi, üretim **kuyruğa alınırken** düşülür; üretim ya da tek bir varyant başarısız olursa o kısım **otomatik iade** edilir.
- Bakiye `users.credit_balance` alanındadır ama her değişiklik `credit_transactions` defterine yazılır. Admin düzeltmesi de bir harekettir. Tüm kredi işlemleri **DB transaction + `lockForUpdate`** içinde yapılır.
- Kayıt bonusu (varsayılan 10 kredi) admin ayarıdır.
- Dekupe ve Prompt'u Geliştir varsayılan olarak ücretsizdir (ayardan ücretlendirilebilir). Kötüye kullanımı önlemek için rate limit uygulanır.

### 3.2 PRO üyelik
- PRO rozetli stil/şablonlar ve **8 varyant** sadece PRO kullanıcılar içindir (sunucu tarafında da doğrulanır).
- `users.pro_expires_at` ile tutulur. Faz 1'de admin atar; ödeme entegrasyonu Faz 3.

### 3.3 İçerik yönetimi
- Stüdyo stilleri, ışık ön ayarları, hızlı sahne türleri, kategoriler, Keşfet şablonları ve kalite seviyeleri **tamamen admin panelden** yönetilir; mobil uygulama bunları API'den çeker.
- Her stil / ışık / sahne kaydı kullanıcıya görünmeyen bir **prompt parçası** taşır; nihai prompt sunucuda birleştirilir ([7.4](#74-prompt-mimarisi)).

### 3.4 Kapsam dışı (şimdilik)
Docker, S3/CDN, web istemcisi, Shopify entegrasyonu, sonuç ekranındaki gelişmiş düzenleme araçları, uygulama içi satın alma.

---

## 4. Sistem Mimarisi

```
┌───────────────┐   HTTPS/JSON (Sanctum Bearer)   ┌─────────────────────────────────────────┐
│ Flutter App   │ ──────────────────────────────► │ Laravel                                  │
│ (iOS/Android) │ ◄────── polling (2–3 sn) ────── │  ├─ /api/v1/*   (mobil API)              │
└───────────────┘                                 │  ├─ /admin      (Blade admin panel)      │
                                                  │  ├─ Jobs: CutoutJob, GenerateVariantJob  │
                                                  │  └─ Services\Ai\OpenRouterClient         │
                                                  └───────┬──────────────────┬───────────────┘
                                                          │                  │
                                                   ┌──────▼─────┐     ┌──────▼────────────┐
                                                   │  MySQL 8   │     │ OpenRouter API    │
                                                   │ + jobs tbl │     │ (görsel & metin)  │
                                                   └────────────┘     └───────────────────┘
                                                   storage/app/public  (orijinal, dekupe, sonuç)
```

### 4.1 Backend katmanları

| Katman | Sorumluluk |
|---|---|
| `Http/Controllers/Api/V1` | İnce controller'lar; doğrulama FormRequest'te, iş mantığı Action/Service'te |
| `Http/Requests` | Girdi doğrulama (dosya tipi/boyutu, prompt uzunluğu, varyant sayısı, PRO kontrolü) |
| `Http/Resources` | JSON çıktıları (tam URL'ler, tutarlı şema) |
| `Actions` | Tek iş yapan sınıflar: `CreateProject`, `StartGeneration`, `RefundCredits`… |
| `Services/Ai` | `OpenRouterClient`, `PromptBuilder`, `ImageStorage` |
| `Services/Credits` | `CreditService` (debit / refund / grant, transaction + kilit) |
| `Jobs` | `CutoutJob`, `GenerateVariantJob`, `FinalizeGenerationJob` |
| `Enums` | `GenerationStatus`, `CreditTransactionType`, `ProjectStatus`, `QualityLevel` |
| `Http/Controllers/Admin` + `resources/views/admin` + `public/panel-assets/admin.css` | Admin paneli (özel CSS, çerçevesiz) |

### 4.2 Üretim akışı (uçtan uca)

1. `POST /projects` (multipart) → orijinal görsel `projects/{id}/original.jpg` olarak kaydedilir (EXIF döndürme düzeltilir, uzun kenar max 2048 px'e küçültülür).
2. `POST /projects/{id}/cutout` → `CutoutJob` kuyruğa; proje `cutout_status = processing`.
3. Mobil `GET /projects/{id}` ile dekupe durumunu izler → `done` olunca `cutout_url` gösterilir.
4. `POST /projects/{id}/generations` → `StartGeneration` action:
   - PRO / kredi / limit kontrolleri
   - `PromptBuilder` ile `final_prompt` oluşturulur
   - Kredi düşülür (`debit`), `generations` kaydı `queued`
   - Her varyant için `generation_images` satırı (`pending`) ve bir `GenerateVariantJob` dispatch edilir (`Bus::batch`)
5. `GenerateVariantJob` → OpenRouter'a istek → dönen base64 görsel `generations/{id}/v{n}.png` olarak kaydedilir → satır `done`.
6. Batch bittiğinde (`then/finally`) `FinalizeGenerationJob`: başarılı varyant sayısına göre durum `completed` / `partial` / `failed`, başarısızlar için **kredi iadesi**, ilk başarılı varyant `is_master = true`.
7. Mobil `GET /generations/{id}` ile her 2–3 sn'de bir sorgular; varyantlar hazır oldukça ekranda belirir.

### 4.3 Kuyruk
- `QUEUE_CONNECTION=database`; Redis gerekmez.
- Worker: `php artisan queue:work --queue=ai,default --tries=3 --timeout=180`.
- `GenerateVariantJob`: `$tries = 3`, `backoff = [10, 30, 60]`, 429/5xx'te tekrar; 4xx (içerik reddi vb.) kalıcı hata.
- Üretimde worker'ı ayakta tutmak için Windows'ta NSSM / Görev Zamanlayıcı, Linux'ta Supervisor kullanılır.

### 4.4 Depolama
```
storage/app/public/
├── projects/{project_id}/original.jpg
├── projects/{project_id}/cutout.png
├── generations/{generation_id}/v1.png … v8.png
├── generations/{generation_id}/thumbs/v1.webp …   (400 px küçük resim)
└── catalog/  (stil, sahne, şablon kapakları — admin yükler)
```
- Görsel işleme: `intervention/image` v3 (yeniden boyutlandırma, thumbnail, EXIF).
- URL'ler `Storage::url()` ile üretilir. İleride S3'e geçiş için yalnızca disk değiştirilir.

### 4.5 Güvenlik
- Mobil kimlik doğrulama: **Sanctum personal access token** (Bearer). Token Flutter'da `flutter_secure_storage`'da.
- Admin: session tabanlı ayrı `admin` guard'ı + `admins` tablosu.
- `OPENROUTER_API_KEY` **yalnızca** sunucuda (`.env`); mobil uygulama OpenRouter'a asla doğrudan gitmez.
- Policy'ler: kullanıcı yalnızca kendi proje/üretimini görebilir.
- Rate limit: `auth` 5/dk, `generations` 10/dk, `enhance-prompt` 20/dk (kullanıcı başına).
- Yükleme doğrulama: `image|mimes:jpg,jpeg,png,webp,heic|max:15360`.

---

## 5. Veritabanı

MySQL 8, `utf8mb4_unicode_ci`. Tüm tablolarda `id` (bigint), `created_at`, `updated_at`.

### 5.1 Tablolar

#### `users`
| Alan | Tip | Not |
|---|---|---|
| name | string | |
| email | string unique | |
| password | string | |
| avatar_path | string null | |
| credit_balance | unsigned int default 0 | Defterden türetilmiş önbellek |
| pro_expires_at | timestamp null | null veya geçmiş → PRO değil |
| is_active | bool default 1 | Admin engelleyebilir |
| last_login_at | timestamp null | |

#### `admins`
`name`, `email` unique, `password`, `role` enum(`super_admin`, `editor`), `remember_token`.

#### `categories` — Keşfet çipleri
`name`, `slug` unique, `icon` (Material Symbols adı), `sort_order`, `is_active`.

#### `studio_styles` — Stüdyo Stili (Lüks Ticari, Minimalist, Cyber Neon…)
| Alan | Tip | Not |
|---|---|---|
| name, slug | string | |
| thumbnail_path | string | |
| prompt_fragment | text | Kullanıcıya görünmez |
| is_pro | bool | |
| sort_order, is_active | | |

#### `scene_types` — Hızlı Sahne Türü (Podyum Üzeri, Havada Asılı, Doğal Taş & Su…)
`name`, `slug`, `thumbnail_path`, `prompt_fragment`, `is_pro`, `sort_order`, `is_active`.

#### `lighting_presets` — Softbox, Spot Işık, Altın Saat, Arkadan Işık
`name`, `subtitle` ("Yumuşak & Eşit"), `icon`, `prompt_fragment`, `is_pro`, `sort_order`, `is_active`.

#### `quality_levels` — Taslak / Dengeli / Ultra 4K
`key` (`draft`/`balanced`/`ultra`), `name`, `credit_multiplier` (decimal 4,2), `image_size` (`1K`/`2K`/`4K`), `model_id` null (kaliteye özel model), `prompt_fragment`, `is_pro`, `sort_order`.

#### `templates` — Keşfet "Popüler Stüdyo Setleri"
| Alan | Tip | Not |
|---|---|---|
| category_id | FK | |
| title, subtitle | string | "Lüks Mermer & Altın", "Pürüzsüz stüdyo yansımaları" |
| cover_path | string | |
| badge | enum null (`pro`, `trend`, `new`) | |
| studio_style_id, scene_type_id, lighting_preset_id | FK null | Kullan'a basınca ön-seçilir |
| default_prompt | text null | |
| quality_key | string null | |
| is_pro, is_featured | bool | |
| likes_count, uses_count | unsigned int | Sayaçlar |
| sort_order, is_active | | |

#### `template_likes`
`user_id`, `template_id` — unique(`user_id`,`template_id`).

#### `projects`
| Alan | Tip | Not |
|---|---|---|
| user_id | FK | |
| title | string null | Ürün adı ("Obsidian Aura No. 1") |
| original_path | string | |
| original_width, original_height | int | |
| cutout_path | string null | |
| cutout_status | enum `pending/processing/done/failed` | |
| cutout_error | text null | |
| scene_type_id | FK null | Adım 1 seçimi |
| shadow_enabled | bool default 1 | Gölge & Yansıma |
| template_id | FK null | Şablondan başladıysa |
| deleted_at | soft delete | |

#### `generations` — bir "Stüdyo Çekimi Oluştur" tıklaması
| Alan | Tip | Not |
|---|---|---|
| project_id, user_id | FK | |
| user_prompt | text null | Kullanıcının yazdığı |
| final_prompt | longText | Modele giden birleşik prompt |
| studio_style_id, scene_type_id, lighting_preset_id, quality_level_id, template_id | FK null | |
| shadow_enabled | bool | |
| aspect_ratio | string default `4:5` | `1:1`, `4:5`, `3:4`, `9:16`, `16:9` |
| variant_count | tinyint | 1/2/4/8 |
| model_id | string | Kullanılan OpenRouter modeli |
| status | enum `queued/processing/completed/partial/failed` | |
| credits_charged | int | |
| credits_refunded | int default 0 | |
| cost_usd | decimal(10,6) default 0 | OpenRouter'ın bildirdiği toplam |
| error_message | text null | |
| started_at, completed_at | timestamp null | |

#### `generation_images` — tek bir varyant
| Alan | Tip | Not |
|---|---|---|
| generation_id | FK | |
| variant_index | tinyint | 1..8 → V1..V8 |
| status | enum `pending/processing/done/failed` | |
| path, thumb_path | string null | |
| width, height | int null | |
| is_master | bool | V1 MASTER |
| is_favorite | bool | Yıldız |
| openrouter_generation_id | string null | Maliyet sorgusu için |
| cost_usd | decimal(10,6) null | |
| error_message | text null | |

#### `credit_transactions`
| Alan | Tip | Not |
|---|---|---|
| user_id | FK | |
| type | enum `signup_bonus/generation/refund/admin_grant/admin_deduct/purchase` | |
| amount | int | + ekleme, − harcama |
| balance_after | int | |
| reference_type, reference_id | morphs null | Generation vb. |
| description | string null | |
| admin_id | FK null | Admin işlemiyse |

#### `ai_request_logs` — maliyet & hata takibi
`user_id`, `generation_image_id` null, `purpose` (`cutout`/`generate`/`enhance_prompt`), `model_id`, `status_code`, `duration_ms`, `prompt_tokens`, `completion_tokens`, `cost_usd`, `error` (text null), `request_meta` (json, görsel base64'ü **hariç**).

#### `settings` — anahtar/değer
`key` unique, `value` (json), `group`. Örnek anahtarlar:

| key | Varsayılan |
|---|---|
| `ai.api_key` | — (admin panelden girilir, `APP_KEY` ile şifreli; yoksa `.env` `OPENROUTER_API_KEY`) |
| `ai.fake_mode` | — (admin panelden; yoksa `.env` `AI_FAKE`) |
| `ai.image_model` | `google/gemini-3.1-flash-lite-image` |
| `ai.cutout_model` | `google/gemini-3.1-flash-lite-image` |
| `ai.text_model` | `openai/gpt-5.4-nano` |
| `ai.system_prompt` | [7.4](#74-prompt-mimarisi)'teki taban metin |
| `credits.signup_bonus` | `10` |
| `credits.cutout_cost` | `0` |
| `credits.enhance_cost` | `0` |
| `limits.max_variants_free` | `4` |
| `app.maintenance` | `false` |
| `app.min_version` | `1.0.0` |

Ayrıca Laravel'in standart tabloları: `personal_access_tokens`, `jobs`, `job_batches`, `failed_jobs`, `cache`, `sessions`.

### 5.2 İlişki özeti

```
users 1─* projects 1─* generations 1─* generation_images
users 1─* credit_transactions
users *─* templates (template_likes)
categories 1─* templates
templates *─1 studio_styles / scene_types / lighting_presets
generations *─1 studio_styles / scene_types / lighting_presets / quality_levels
```

### 5.3 Seed verisi
Tasarımlardaki içerik birebir seed edilir: 6 kategori (Kozmetik, Parfüm & Cam, Sneaker, Organik, Cilt Bakımı, İçecek, Mücevher), 5 sahne türü, 5 stil (Yok/Ham dahil), 4 ışık, 3 kalite, 6 şablon (Lüks Mermer & Altın, Podium & Neon Cyber, Doğal Gün Işığı & Ahşap, Minimal Pastel Gölge, Su Damlaları & Buz, Dramatik Sinematik), 1 super admin, 1 demo kullanıcı.

---

## 6. REST API

- Taban: `/api/v1` — JSON, `Accept: application/json`
- Kimlik: `Authorization: Bearer {token}` (Sanctum)
- Görsel URL'leri her zaman **mutlak** URL döner.
- Liste uç noktaları `?page=` ile sayfalanır (`data`, `links`, `meta`).

### 6.1 Hata formatı
```json
{ "message": "Yetersiz kredi.", "code": "INSUFFICIENT_CREDITS", "errors": {} }
```
| HTTP | code | Durum |
|---|---|---|
| 401 | `UNAUTHENTICATED` | Token yok / geçersiz |
| 402 | `INSUFFICIENT_CREDITS` | Kredi yetmiyor |
| 403 | `PRO_REQUIRED` | PRO içerik / 8 varyant |
| 403 | `ACCOUNT_DISABLED` | Hesap engelli |
| 404 | `NOT_FOUND` | |
| 422 | `VALIDATION_ERROR` | `errors` alan bazlı |
| 429 | `RATE_LIMITED` | |
| 503 | `MAINTENANCE` | Bakım modu |

### 6.2 Uç noktalar

#### Kimlik
| Metot | Yol | Açıklama |
|---|---|---|
| POST | `/auth/register` | `name, email, password, device_name` → `{ token, user }` (kayıt bonusu yazılır) |
| POST | `/auth/login` | `email, password, device_name` → `{ token, user }` |
| POST | `/auth/logout` | Mevcut token silinir |
| POST | `/auth/forgot-password` | Sıfırlama e-postası |
| GET | `/me` | Kullanıcı + `credit_balance` + `is_pro` + `pro_expires_at` |
| PUT | `/me` | Ad, avatar |
| DELETE | `/me` | Hesap silme (mağaza zorunluluğu) |
| GET | `/me/credit-transactions` | Kredi geçmişi |

#### Uygulama & katalog
| Metot | Yol | Açıklama |
|---|---|---|
| GET | `/app/config` | `min_version`, `maintenance`, varyant seçenekleri, kalite seviyeleri, oranlar, prompt max uzunluk |
| GET | `/catalog` | Tek çağrıda `categories`, `studio_styles`, `scene_types`, `lighting_presets`, `quality_levels` (uygulama açılışında önbelleğe alınır) |
| GET | `/templates` | `?category=slug&search=&featured=1` |
| GET | `/templates/{id}` | |
| POST | `/templates/{id}/like` | Beğen/geri al (toggle) → `{ liked, likes_count }` |

#### Projeler
| Metot | Yol | Açıklama |
|---|---|---|
| GET | `/projects` | Kullanıcının projeleri (son üretimin master küçük resmiyle) |
| POST | `/projects` | multipart: `image`, `title?`, `template_id?` → proje |
| GET | `/projects/{id}` | Proje + dekupe durumu + üretimler |
| PATCH | `/projects/{id}` | `title`, `scene_type_id`, `shadow_enabled` |
| POST | `/projects/{id}/image` | Fotoğrafı **Değiştir** (dekupe sıfırlanır) |
| POST | `/projects/{id}/cutout` | Dekupeyi başlat / yeniden dene → 202 |
| DELETE | `/projects/{id}` | Soft delete |

#### Üretim
| Metot | Yol | Açıklama |
|---|---|---|
| POST | `/prompt/enhance` | `{ prompt, studio_style_id?, lighting_preset_id? }` → `{ prompt }` |
| POST | `/generations/estimate` | `{ variant_count, quality_level_id }` → `{ credits, balance, enough }` |
| POST | `/projects/{id}/generations` | Üretimi başlat → 202 + generation |
| GET | `/generations/{id}` | Durum + varyantlar (polling) |
| POST | `/generations/{id}/more` | `{ count }` — aynı ayarlarla ek varyant ("+4 Üret") |
| PATCH | `/generation-images/{id}` | `{ is_favorite?, is_master? }` |
| GET | `/generation-images/{id}/download` | Tam çözünürlük dosya (Content-Disposition) |
| GET | `/favorites` | Favori görseller |

#### Örnek — üretim başlatma
```http
POST /api/v1/projects/42/generations
{
  "user_prompt": "Lüks siyah mermer podyum üzerinde duran cam parfüm şişesi, arkadan vuran sıcak amber stüdyo ışığı…",
  "studio_style_id": 2,
  "lighting_preset_id": 2,
  "quality_level_id": 3,
  "scene_type_id": 1,
  "shadow_enabled": true,
  "aspect_ratio": "4:5",
  "variant_count": 4,
  "template_id": null
}
```
```json
202 Accepted
{
  "data": {
    "id": 118,
    "status": "queued",
    "variant_count": 4,
    "credits_charged": 4,
    "credit_balance": 36,
    "model_label": "Studio Diffusion XL",
    "images": [
      { "id": 501, "variant_index": 1, "status": "pending", "url": null, "thumb_url": null }
    ]
  }
}
```

#### Örnek — polling yanıtı
```json
{
  "data": {
    "id": 118,
    "status": "processing",
    "ready_count": 2,
    "variant_count": 4,
    "user_prompt": "…",
    "aspect_ratio": "4:5",
    "project": { "id": 42, "title": "Obsidian Aura No. 1", "original_url": "https://…/original.jpg" },
    "images": [
      { "id": 501, "variant_index": 1, "status": "done", "is_master": true, "is_favorite": false,
        "url": "https://…/v1.png", "thumb_url": "https://…/thumbs/v1.webp", "width": 2160, "height": 2700 },
      { "id": 502, "variant_index": 2, "status": "processing", "url": null }
    ]
  }
}
```

> `model_label` kullanıcıya gösterilen pazarlama adıdır (admin ayarı); gerçek model kimliği istemciye gönderilmez.

---

## 7. OpenRouter Entegrasyonu

### 7.1 Neden OpenRouter
Tek API anahtarıyla birden çok görsel üretim modeline erişim; model değiştirmek kod değişikliği değil **admin ayarı** olur. OpenAI uyumlu `chat/completions` şeması kullanılır.

### 7.2 Model seçimi
| Amaç | Varsayılan (admin'den değiştirilebilir) | Not |
|---|---|---|
| Stüdyo görseli üretimi + düzenleme araçları | `google/gemini-3.1-flash-lite-image` | Nano Banana 2 Lite — kaliteli modeller içinde en ekonomik (~0.03–0.04 $/görsel) |
| Dekupe | `google/gemini-3.1-flash-lite-image` | Aynı ekonomik model |
| Prompt'u Geliştir | `openai/gpt-5.4-nano` | En ucuz OpenAI metin modellerinden |
| Alternatif | `google/gemini-2.5-flash-image`, `openai/gpt-5.4-image-2` (en yüksek kalite, pahalı) | Admin → Ayarlar'dan değiştirilebilir |

> Model kimlikleri ve fiyatları değişebilir. Geliştirmeye başlarken `https://openrouter.ai/models?output_modalities=image` üzerinden güncel listeyi kontrol edip `settings` tablosuna yazın. Admin panelde "Bağlantıyı Test Et" butonu olmalıdır.

### 7.3 İstek / yanıt formatı

**Endpoint:** `POST https://openrouter.ai/api/v1/chat/completions`

**Header'lar:**
```
Authorization: Bearer {OPENROUTER_API_KEY}
Content-Type: application/json
HTTP-Referer: {APP_URL}
X-Title: StudioAI
```

**Görsel üretim isteği (image-to-image):**
```json
{
  "model": "google/gemini-2.5-flash-image",
  "modalities": ["image", "text"],
  "image_config": { "aspect_ratio": "4:5" },
  "messages": [
    { "role": "system", "content": "{{final_system_prompt}}" },
    {
      "role": "user",
      "content": [
        { "type": "text", "text": "{{final_user_prompt}}" },
        { "type": "image_url", "image_url": { "url": "data:image/png;base64,{{cutout_or_original}}" } }
      ]
    }
  ]
}
```

**Yanıt (özet):**
```json
{
  "id": "gen-…",
  "choices": [{
    "message": {
      "role": "assistant",
      "content": "…",
      "images": [ { "type": "image_url", "image_url": { "url": "data:image/png;base64,iVBOR…" } } ]
    }
  }],
  "usage": { "prompt_tokens": 1290, "completion_tokens": 1300, "cost": 0.039 }
}
```

- Görsel `choices[0].message.images[0].image_url.url` içindeki **base64 data URL**'dir → decode edilip diske yazılır.
- `images` boşsa (model sadece metin döndüyse veya içerik reddi) → varyant `failed`, tekrar denenir; tekrarlar da boşsa kalıcı hata + iade.
- Maliyet: `usage.cost` alanı varsa loglanır; yoksa `GET /api/v1/generation?id={id}` ile sonradan sorgulanır.
- HTTP istemcisi: Laravel `Http::withToken()->timeout(150)->retry(2, 2000, when: 429/5xx)`.

### 7.4 Prompt mimarisi

Nihai prompt `PromptBuilder` tarafından katmanlı olarak birleştirilir:

```
[1] Sistem (settings.ai.system_prompt)
[2] Sahne türü      → scene_types.prompt_fragment
[3] Stüdyo stili    → studio_styles.prompt_fragment
[4] Işıklandırma    → lighting_presets.prompt_fragment
[5] Gölge/Yansıma   → shadow_enabled ? "realistic contact shadow and subtle floor reflection" : "no shadow"
[6] Kalite          → quality_levels.prompt_fragment
[7] Kullanıcı       → user_prompt (serbest metin, Türkçe olabilir)
[8] Oran            → aspect_ratio (hem image_config hem metin)
```

**Taban sistem prompt'u (varsayılan):**
```
You are a world-class commercial product photographer and retoucher.
Take the product in the provided image and place it in a new professional studio scene.
STRICT RULES:
- Keep the product IDENTICAL: same shape, proportions, colors, materials, label, logo and all printed text. Do not redraw, translate or invent text.
- Only change the environment: background, surface, props, lighting and atmosphere.
- Photorealistic, high-end advertising quality, sharp focus on the product, physically correct lighting, reflections and shadows.
- No people, no hands, no watermarks, no extra text, no borders.
- Output a single image.
```

**Kullanıcı mesajı şablonu:**
```
Scene: {scene}. Style: {style}. Lighting: {lighting}. {shadow}. {quality}.
Aspect ratio: {aspect_ratio}.
Additional art direction from the user (may be Turkish): "{user_prompt}"
```

**Varyant çeşitliliği:** Her varyanta küçük bir ek ipucu eklenir (`camera angle: eye-level`, `slightly elevated 30°`, `close-up hero`, `wider composition` …) — aynı ayarlarla birbirinin kopyası olmayan sonuçlar üretilir.

### 7.5 Dekupe (arka plan kaldırma)
- **Faz 1:** Görsel modeline dekupe prompt'u gönderilir:
  `"Isolate the product exactly as it is. Remove the entire background and replace it with a perfectly uniform pure white (#FFFFFF) background. Do not alter the product."`
  Sonuç `cutout.png` olarak kaydedilir; mobilde önizlenir ve **üretimde girdi olarak bu görsel** kullanılır (daha temiz sonuç).
- **Faz 2 (isteğe bağlı):** Gerçek şeffaf PNG için cihaz üzerinde segmentasyon (Android: ML Kit Subject Segmentation, iOS 17+: Vision foreground mask) veya OpenRouter'da şeffaflık destekleyen bir model. Tasarımdaki dama desenli şeffaf önizleme bu fazda birebir karşılanır; Faz 1'de beyaz zemin dama desenli çerçeve içinde gösterilir.
- **Kenar Düzelt:** Faz 1'de dekupeyi "daha hassas kenarlarla" tekrar çalıştırır; **Yeniden Kırp:** mobilde `image_cropper` ile kırpılıp `POST /projects/{id}/image` ile yeniden yüklenir.

### 7.6 Prompt'u Geliştir
Metin modeline sistem mesajı: *"Kullanıcının ürün çekimi açıklamasını, seçilen stil ve ışığı dikkate alarak tek paragraf, en fazla 500 karakter, Türkçe, ticari fotoğraf terimleriyle zenginleştir. Sadece prompt'u döndür."* → yanıt 500 karakterde kesilir.

### 7.7 Maliyet kontrolü
- Her istek `ai_request_logs`'a yazılır; admin dashboard'da günlük/aylık USD maliyet grafiği.
- `settings.ai.daily_budget_usd` aşılırsa yeni üretimler 503 `AI_BUDGET_EXCEEDED` döner ve admin'e bildirim düşer.
- Test için `AI_FAKE=true` ortam değişkeni: `FakeOpenRouterClient` örnek görseller döndürür, API'ye para harcanmaz.

---

## 8. Admin Panel

Çerçeve kullanılmaz: Blade şablonları + `public/panel-assets/admin.css` (Obsidian Amber token'ları) + küçük bir `admin.js`. Yol `/admin`, `admin` guard'ı, Türkçe arayüz. Katalog kaynakları tek bir yapılandırmalı CRUD controller'ı (`Admin\CatalogController`) ile yönetilir.

### 8.1 Dashboard
- Kart widget'ları: toplam kullanıcı, bugünkü üretim, başarı oranı, bugünkü/aylık AI maliyeti (USD), kuyrukta bekleyen iş, başarısız iş
- Grafikler: son 30 gün üretim sayısı, son 30 gün maliyet
- Tablo: son 10 üretim (küçük resimlerle), son başarısız işler

### 8.2 Kaynaklar (Resources)

| Menü grubu | Kaynak | Özellikler |
|---|---|---|
| **Kullanıcılar** | Kullanıcılar | Liste/arama/filtre (PRO, aktif), detayda projeler & kredi geçmişi; aksiyonlar: **Kredi Ekle/Düş** (açıklamalı, deftere yazar), **PRO Ver** (tarih seç), Engelle/Aç |
| | Kredi Hareketleri | Salt okunur defter, tür/kullanıcı/tarih filtresi |
| **Üretimler** | Projeler | Orijinal / dekupe görseli, üretimler |
| | Üretimler | Durum filtresi, final prompt görüntüleme, varyant galerisi, maliyet; aksiyonlar: **Yeniden Dene**, **Kredi İade Et** |
| **Katalog** | Kategoriler | Sıralama (sürükle-bırak), ikon, aktif |
| | Stüdyo Stilleri | Küçük resim yükleme, prompt parçası, PRO, sıralama |
| | Sahne Türleri | Aynı |
| | Işık Ön Ayarları | Ad, alt başlık, ikon, prompt parçası |
| | Kalite Seviyeleri | Kredi çarpanı, çözünürlük, özel model |
| | Şablonlar | Kapak, rozet, kategori, varsayılan stil/ışık/sahne/prompt, öne çıkan, sayaçlar; aksiyon: **Prompt Önizle** (PromptBuilder çıktısı) |
| **Sistem** | Ayarlar (Settings sayfası) | Sekmeler: AI (modeller, sistem prompt, model etiketi, günlük bütçe, **Bağlantıyı Test Et**), Kredi (bonus, dekupe/geliştirme ücreti), Limitler, Uygulama (bakım modu, min. sürüm) |
| | AI İstek Logları | Model, süre, maliyet, hata; salt okunur |
| | Başarısız İşler | `failed_jobs` görüntüleme, tekrar kuyruğa alma |
| | Yöneticiler | Sadece `super_admin` |

### 8.3 Yetkiler
| Rol | Yetki |
|---|---|
| `super_admin` | Her şey |
| `editor` | Katalog + şablonlar + üretimleri görme; kullanıcı kredisi, ayarlar ve yöneticiler **hariç** |

### 8.4 Playground (Faz 2)
Admin'in bir görsel yükleyip stil/ışık/prompt kombinasyonlarını kredi harcamadan test ettiği sayfa — yeni şablon hazırlarken prompt parçalarını ayarlamak için.

---

## 9. Flutter Uygulaması

### 9.1 Paketler
| Amaç | Paket |
|---|---|
| State yönetimi | `flutter_riverpod`, `riverpod_annotation` (+ `riverpod_generator`) |
| Yönlendirme | `go_router` (ShellRoute ile alt navigasyon) |
| HTTP | `dio` (interceptor: token, hata eşleme, 401 → login) |
| Modeller | `freezed`, `json_serializable` |
| Güvenli depolama | `flutter_secure_storage` |
| Basit tercihler | `shared_preferences` |
| Görsel seçme/kırpma | `image_picker`, `image_cropper` |
| Görsel sıkıştırma | `flutter_image_compress` (yükleme öncesi max 2048 px, JPEG %90) |
| Görsel gösterimi | `cached_network_image` |
| Yakınlaştırma | `photo_view` veya `InteractiveViewer` |
| Galeriye kaydetme | `gal` |
| Paylaşma | `share_plus` |
| İzinler | `permission_handler` |
| Yükleme iskeleti | `shimmer` |
| Font | `google_fonts` (Plus Jakarta Sans) veya font dosyası `assets/fonts` |
| İkon | `material_symbols_icons` (tasarım Material Symbols kullanıyor) |
| Yerelleştirme | `flutter_localizations` + `intl` (başlangıçta tr) |

### 9.2 Klasör yapısı (feature-first)
```
mobile/lib/
├── main.dart
├── app.dart                         # MaterialApp.router, tema
├── core/
│   ├── config/env.dart              # API_BASE_URL (--dart-define)
│   ├── network/dio_client.dart      # interceptor'lar
│   ├── network/api_exception.dart   # code → Türkçe mesaj
│   ├── router/app_router.dart
│   ├── storage/token_storage.dart
│   ├── theme/app_colors.dart
│   ├── theme/app_typography.dart
│   ├── theme/app_theme.dart
│   └── widgets/                     # ortak bileşenler
│       ├── primary_button.dart      # amber glow CTA
│       ├── glass_chip.dart
│       ├── glass_dock.dart          # yüzen alt navigasyon
│       ├── pro_badge.dart
│       ├── section_header.dart
│       ├── style_thumb.dart
│       ├── segmented_counter.dart
│       ├── amber_slider.dart
│       └── before_after_slider.dart
└── features/
    ├── auth/        (data / domain / presentation)
    ├── catalog/     # /catalog önbelleği — stil, sahne, ışık, kalite
    ├── discover/    # Keşfet / Şablonlar
    ├── studio/      # editör akışı: upload → dekupe → ayarlar
    │   ├── presentation/upload_screen.dart
    │   ├── presentation/cutout_screen.dart        # Ön Yükleme & Segmentasyon
    │   ├── presentation/studio_settings_screen.dart
    │   └── application/studio_draft_controller.dart
    ├── generation/  # Sonuçlar & Karşılaştırma, polling
    ├── projects/
    └── profile/
```
Her feature içinde: `data/` (repository + API), `domain/` (freezed modeller), `presentation/` (ekran + widget), `application/` (Riverpod controller'lar).

### 9.3 Yönlendirme
```
/splash
/onboarding
/login  /register  /forgot-password
ShellRoute (GlassDock)
 ├── /discover
 ├── /studio                         # yeni çekim başlangıcı (fotoğraf seç)
 ├── /projects
 └── /profile
/studio/:projectId/cutout            # adım 1
/studio/:projectId/settings          # adım 2
/generations/:generationId           # adım 3 + sonuçlar
/templates/:id
```
Guard: token yoksa `/login`'e; `app/config` bakım/min. sürüm kontrolü splash'te.

### 9.4 Ekran ↔ API eşlemesi
| Ekran | Çağrılar |
|---|---|
| Splash | `GET /app/config`, `GET /me`, `GET /catalog` |
| Keşfet | `GET /templates?category=&search=`, `POST /templates/{id}/like`; **Kullan** → fotoğraf seç → `POST /projects` (`template_id`) |
| Fotoğraf seçimi | `POST /projects` → `POST /projects/{id}/cutout` |
| Ön Yükleme & Segmentasyon | `GET /projects/{id}` (dekupe polling), `PATCH /projects/{id}` (sahne, gölge), `POST /projects/{id}/image` (Değiştir / Yeniden Kırp), `POST /projects/{id}/cutout` (Kenar Düzelt) |
| Stüdyo Ayarları | `POST /prompt/enhance`, `POST /generations/estimate`, `POST /projects/{id}/generations` |
| Sonuçlar | `GET /generations/{id}` (polling), `PATCH /generation-images/{id}`, `GET …/download`, `POST /generations/{id}/more` |
| Projeler | `GET /projects` |
| Profil | `GET /me`, `GET /me/credit-transactions`, `POST /auth/logout` |

### 9.5 Önemli davranışlar
- **Stüdyo taslağı:** `StudioDraftController` (Riverpod `Notifier`) proje id, sahne, gölge, prompt, stil, ışık, kalite, varyant sayısını tutar. Şablondan gelindiyse değerler ön-doldurulur. Uygulama arka plana düşse de kaybolmaması için `shared_preferences`'a yazılır.
- **Kredi tahmini:** Varyant/kalite değiştikçe istemcide `variant × multiplier` hesaplanır (katalogtan); gönderimde sunucu son sözü söyler. Yetersizse CTA pasif + "Kredi yetersiz" uyarısı.
- **Polling:** `GenerationController` `Timer.periodic(2.5 sn)`; `completed/partial/failed` olunca durur; ekran kapanınca iptal. 3 dk'dan uzun sürerse kullanıcıya "Hazır olunca Projeler'de görebilirsin" gösterilir.
- **Önce/Sonra karşılaştırma:** `Stack` + `ClipRect` + sürüklenebilir dikey amber çizgi ve yuvarlak tutamaç (`GestureDetector.onHorizontalDragUpdate`).
- **PRO kilitleri:** PRO içerikler kilit ikonu ile gösterilir; tıklanınca PRO bilgilendirme bottom sheet'i.
- **İndirme:** tam çözünürlük dosya indirilir → `gal` ile galeriye kaydedilir. "Tümünü İndir" hepsini sırayla kaydeder ve ilerleme gösterir.
- **Hata eşleme:** `ApiException.code` → Türkçe kullanıcı mesajı (`INSUFFICIENT_CREDITS` → "Yeterli kredin yok").
- **İzinler:** iOS `Info.plist` → `NSCameraUsageDescription`, `NSPhotoLibraryUsageDescription`, `NSPhotoLibraryAddUsageDescription`; Android 13+ `READ_MEDIA_IMAGES`.
- **Emulator'dan yerel API:** Android emulator için `http://10.0.2.2:8000`, iOS simulator için `http://127.0.0.1:8000`, gerçek cihaz için bilgisayarın LAN IP'si (`php artisan serve --host=0.0.0.0`). Geliştirmede HTTP için Android `usesCleartextTraffic` debug manifest'te açılır.

---

## 10. Tasarım Sistemi → Flutter

Kaynak: `ekrantasarimlari/obsidian_amber/DESIGN.md`. Uygulama **yalnızca koyu tema** ile gelir.

### 10.1 Renkler (`AppColors`)
| Token | Değer | Kullanım |
|---|---|---|
| `background` | `#0F0F12` | Kök zemin (L0) |
| `surface` | `#18181C` | Kartlar, paneller (L1) |
| `surfaceElevated` | `#222228` | Aktif kontroller, ikincil butonlar (L2) |
| `glass` | `rgba(34,34,40,0.75)` + blur 16 | Bottom sheet, dock |
| `border` | `rgba(255,255,255,0.08)` | Kart kenarları |
| `borderStrong` | `rgba(255,255,255,0.12)` | Yüzen katman kenarları |
| `primary` | `#FFB800` | CTA, seçili durum, slider |
| `secondary` | `#F5A623` | Vurgu, aktif kenarlık |
| `tertiary` | `#E08A00` | Basılı durum, aktif çip zemini |
| `primaryLight` | `#FFDCA1` | Seçili ışık kartı zemini (tasarımdaki açık amber) |
| `onPrimary` | `#0F0F12` | Amber üstü metin |
| `textPrimary` | `#FFFFFF` | |
| `textSecondary` | `#9D9DA8` | |
| `textTertiary` | `#5C5C66` | Placeholder |
| `error` | `#FFB4AB` | |

`ColorScheme.dark(...)` bu değerlerle kurulur; M3 token'ları (`surfaceContainer*`, `outline` vb.) DESIGN.md başlığındaki değerlerden alınır.

### 10.2 Tipografi (`AppTypography`) — Plus Jakarta Sans
| Stil | Boyut / Satır | Ağırlık | Harf aralığı |
|---|---|---|---|
| headlineXl | 32 / 40 | 700 | −0.02em |
| headlineLg | 24 / 32 | 600 | −0.015em |
| headlineMd | 20 / 28 | 600 | −0.01em |
| titleMd | 16 / 22 | 600 | −0.005em |
| bodyLg | 16 / 24 | 400 | 0 |
| bodyMd | 14 / 20 | 400 | 0 |
| bodySm | 12 / 16 | 400 | 0 |
| labelLg | 14 / 18 | 600 | 0.01em |
| labelMd | 12 / 16 | 600 | 0.02em |
| labelSm | 10 / 14 | 700 | 0.04em, BÜYÜK HARF |

> Flutter'da `letterSpacing` piksel cinsindendir: `em × fontSize` (örn. 32 × −0.02 = −0.64).
> Dart'ın `toUpperCase()` metodu Türkçe "i → İ" dönüşümünü yapmaz; `labelSm` metinleri için küçük bir `trUpper()` yardımcısı yazılır (`i→İ`, `ı→I` sonra `toUpperCase()`).

### 10.3 Boşluk, köşe, gölge
| Token | Değer |
|---|---|
| `margin` | 16 |
| `gutter` | 12 |
| `spaceXs / Sm / Md / Lg / Xl` | 4 / 8 / 12 / 20 / 28 |
| `radiusSm / Default / Md / Lg / Xl / Full` | 4 / 8 / 12 / 16 / 24 / 999 |
| Amber glow | `BoxShadow(color: #FFB800 @ 0.28, blurRadius: 24, offset: (0, 8))` |
| Derin gölge | `BoxShadow(color: #000 @ 0.5, blurRadius: 32, offset: (0, 12))` |

### 10.4 Bileşen kuralları
- **PrimaryButton:** yükseklik 52, radius 24 (veya tam pill), zemin `#FFB800`, metin `#0F0F12` bold, amber glow.
- **SecondaryButton:** zemin `#222228`, 1 px `border`, beyaz metin.
- **GlassChip:** pill, `rgba(255,255,255,0.06)` zemin; aktifken amber zemin + koyu metin.
- **StyleThumb:** kare, radius 12–16, altta gradyan, seçiliyken amber kenarlık + köşede onay rozeti.
- **PromptInput:** `#18181C` zemin, odakta `#FFB800` kenarlık, sağ altta sayaç + "Prompt'u Geliştir".
- **AmberSlider:** `SliderTheme` — aktif iz amber, pasif iz koyu, amber hale ile yuvarlak tutamaç; 3 durak (Taslak / Dengeli / Ultra 4K).
- **SegmentedCounter:** 1/2/4/8 kutuları, seçili amber dolgu.
- **GlassDock:** `BackdropFilter(blur: 20)` + `rgba(24,24,28,0.85)`, alttan 28 px güvenli boşluk, seçili ikon amber.
- **ProBadge:** amber cam zemin, amber kenarlık, taç/rozet ikonu + `labelSm`.

---

## 11. Kurulum (Docker'sız)

### 11.1 Gereksinimler
- PHP 8.2+ (eklentiler: `pdo_mysql`, `mbstring`, `gd` veya `imagick`, `fileinfo`, `openssl`, `curl`, `zip`, `intl`, `exif`)
- Composer 2
- MySQL 8 (Laragon / XAMPP / yerel)
- Node.js 20+ (yalnızca `composer run dev` içindeki `concurrently` için)
- Flutter 3.x (stable) + Android Studio / Xcode
- OpenRouter hesabı ve API anahtarı

### 11.2 Backend
```bash
composer create-project laravel/laravel backend
cd backend
composer require laravel/sanctum intervention/image intervention/image-laravel
php artisan install:api
```
`.env`:
```env
APP_NAME=StudioAI
APP_URL=http://localhost:8000
APP_LOCALE=tr

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=studioai
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

OPENROUTER_API_KEY=sk-or-...
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
AI_FAKE=false
```
```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
composer run dev              # serve + queue:listen + vite aynı anda
```
Ayrı ayrı çalıştırmak isterseniz:
```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan queue:work --queue=ai,default --timeout=180
```
- Admin: `http://localhost:8000/admin` (seed: `admin@studioai.test` / `password`)
- `php.ini`: `upload_max_filesize = 20M`, `post_max_size = 25M`, `memory_limit = 512M`

### 11.3 Mobil
```bash
flutter create --org com.studioai --platforms=android,ios mobile
cd mobile
flutter pub get
dart run build_runner build --delete-conflicting-outputs
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

**USB ile bağlı gerçek Android telefonda** (güvenlik duvarı ayarı gerektirmez):
```bash
adb reverse tcp:8000 tcp:8000
flutter run -d <cihaz-id> --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1
```
Seed hesapları: mobil `demo@studioai.test` / `password` (PRO, 100 kredi), admin `admin@studioai.test` / `password`.
`AI_FAKE=true` iken (veya `OPENROUTER_API_KEY` boşken) görseller OpenRouter'a gitmeden sahte olarak üretilir.

### 11.4 Test
- Backend: Pest/PHPUnit feature testleri; OpenRouter `Http::fake()` ile taklit edilir (gerçek API'ye istek atılmaz).
- Kritik testler: kredi düşme/iade, PRO kontrolü, polling çıktısı, yetkisiz erişim (başka kullanıcının projesi), job başarısızlık senaryoları.
- Mobil: controller'lar için unit test, ana bileşenler için widget test.

---

## 12. Yol Haritası

### Faz 0 — Altyapı
- [x] Laravel projesi, Sanctum, özel CSS admin panel kurulumu
- [ ] Migration'lar, model'ler, enum'lar, seed verisi
- [ ] Flutter projesi, tema (Obsidian Amber), router, dio, ortak bileşenler

### Faz 1 — MVP (ana akış çalışır)
**Backend**
- [ ] Auth uç noktaları + kayıt bonusu
- [ ] `/app/config`, `/catalog`, `/templates` (+ beğeni)
- [ ] Proje yükleme, görsel işleme, dekupe job'ı
- [ ] `OpenRouterClient`, `PromptBuilder`, `FakeOpenRouterClient`
- [ ] Üretim başlatma, varyant job'ları, finalize + iade, polling
- [ ] Prompt'u Geliştir, kredi tahmini
- [ ] Favori, master seçimi, indirme, ek varyant
- [ ] Admin: kullanıcılar (kredi/PRO), katalog kaynakları, şablonlar, üretimler, ayarlar, dashboard

**Mobil**
- [ ] Splash, geçici giriş/kayıt ekranları
- [ ] Keşfet / Şablonlar
- [ ] Fotoğraf seçme + Ön Yükleme & Segmentasyon
- [ ] Stüdyo Ayarları & Prompt
- [ ] Sonuçlar & Karşılaştırma (önce/sonra, varyantlar, indir, paylaş, favori, +Üret)
- [ ] Geçici Projeler ve Profil ekranları

**Kabul kriteri:** Kullanıcı kayıt olur, fotoğraf yükler, dekupe görür, ayarları seçer, 4 varyant üretir, karşılaştırır, galeriye kaydeder; kredi doğru düşer, hata olursa iade edilir; admin tüm süreci panelden izler ve katalogu yönetir.

### Faz 2 — İyileştirme
- [ ] Gelen tasarımlarla Giriş, Projeler, Profil ekranlarının son hali
- [ ] Gerçek şeffaf dekupe (cihaz üstü segmentasyon)
- [ ] Admin Playground, maliyet bütçesi uyarıları
- [ ] Sonuç ekranı araçları: kırp, oran değiştir, yeniden ışıklandır
- [ ] Push bildirimi (üretim tamamlandı) — FCM
- [ ] Google / Apple ile giriş

### Faz 3 — Gelir & Entegrasyon
- [ ] Uygulama içi satın alma (kredi paketleri + PRO abonelik; `in_app_purchase` veya RevenueCat) ve sunucu tarafı makbuz doğrulama
- [ ] Shopify'a Aktar
- [ ] Katalog paylaşım sayfası (web linki)
- [ ] S3 / CDN'e geçiş

---

## 13. Klasör Yapısı

```
part3/
├── PROJE_DOKUMANI.md           ← bu doküman
├── ekrantasarimlari/           ← ekran tasarımları + Obsidian Amber tasarım sistemi
├── backend/                    ← Laravel API + Blade admin panel
│   ├── app/
│   │   ├── Actions/
│   │   ├── Enums/
│   │   ├── Http/{Controllers/Api/V1, Requests, Resources, Middleware}
│   │   ├── Jobs/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Services/{Ai, Credits}
│   ├── database/{migrations, seeders, factories}
│   ├── routes/{api.php, web.php}
│   └── tests/
└── mobile/                     ← Flutter uygulaması
    ├── lib/{core, features}
    ├── assets/{fonts, images}
    └── test/
```
