<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Generation;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Yönetim paneline giriş yap');
    }

    public function test_admin_can_log_in(): void
    {
        $this->post('/admin/login', ['email' => 'admin@studioai.test', 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->admin, 'admin');
    }

    public function test_every_admin_page_renders(): void
    {
        $user = User::first();
        $project = $user->projects()->create(['original_path' => 'x.jpg', 'cutout_status' => 'done']);
        $generation = Generation::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'final_prompt' => 'p',
            'variant_count' => 1, 'model_id' => 'm', 'status' => 'failed', 'credits_charged' => 1,
        ]);
        $generation->images()->create(['variant_index' => 1, 'status' => 'failed', 'error_message' => 'x']);

        $pages = [
            '/admin', '/admin/users', "/admin/users/{$user->id}", '/admin/generations', "/admin/generations/{$generation->id}",
            '/admin/settings', '/admin/logs/ai', '/admin/logs/credits', '/admin/logs/failed-jobs', '/admin/admins',
        ];
        foreach (['categories', 'studio-styles', 'scene-types', 'lighting-presets', 'quality-levels', 'templates'] as $resource) {
            $pages[] = "/admin/catalog/{$resource}";
            $pages[] = "/admin/catalog/{$resource}/create";
            $pages[] = "/admin/catalog/{$resource}/1/edit";
        }
        $pages[] = '/admin/catalog/templates/'.Template::first()->id.'/preview';

        foreach ($pages as $page) {
            $this->actingAs($this->admin, 'admin')->get($page)->assertOk();
        }
    }

    public function test_admin_can_adjust_credits_and_grant_pro(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/users/{$user->id}/credits", ['amount' => 25, 'description' => 'Telafi'])
            ->assertSessionHas('success');
        $this->assertSame(25, $user->fresh()->credit_balance);

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/users/{$user->id}/credits", ['amount' => -100, 'description' => 'Hata'])
            ->assertSessionHas('error');
        $this->assertSame(25, $user->fresh()->credit_balance);

        $this->actingAs($this->admin, 'admin')->post("/admin/users/{$user->id}/pro", ['pro_expires_at' => now()->addMonth()->toDateString()]);
        $this->assertTrue($user->fresh()->isPro());
    }

    public function test_admin_can_create_catalog_item(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post('/admin/catalog/lighting-presets', ['name' => 'Neon Işık', 'subtitle' => 'Renkli', 'icon' => 'flare', 'is_active' => '1'])
            ->assertRedirect('/admin/catalog/lighting-presets');

        $this->assertDatabaseHas('lighting_presets', ['name' => 'Neon Işık', 'slug' => 'neon-isik', 'is_active' => true]);
    }

    public function test_editor_cannot_change_settings(): void
    {
        $editor = Admin::create(['name' => 'E', 'email' => 'e@x.com', 'password' => 'password', 'role' => AdminRole::Editor]);

        $this->actingAs($editor, 'admin')->post('/admin/settings', [])->assertForbidden();
        $this->actingAs($editor, 'admin')->get('/admin/admins')->assertForbidden();
    }

    public function test_api_key_is_stored_encrypted_and_switches_to_live_mode(): void
    {
        $key = 'sk-or-v1-'.str_repeat('a1b2', 12);

        $this->assertTrue(\App\Support\AiConfig::isFake());
        $this->assertInstanceOf(\App\Services\Ai\FakeAiClient::class, app(\App\Services\Ai\AiClient::class));

        $this->actingAs($this->admin, 'admin')->post('/admin/settings', ['ai_api_key' => $key])->assertSessionHas('success');

        $stored = \App\Models\Setting::where('key', 'ai.api_key')->value('value');
        $this->assertNotSame($key, $stored); // şifreli
        $this->assertSame($key, \App\Support\AiConfig::apiKey());
        $this->assertFalse(\App\Support\AiConfig::isFake()); // form sahte modu kapalı gönderdi
        $this->assertInstanceOf(\App\Services\Ai\OpenRouterClient::class, app(\App\Services\Ai\AiClient::class));

        // Sayfa anahtarı yalnızca maskeli gösterir.
        $this->actingAs($this->admin, 'admin')->get('/admin/settings')
            ->assertSee('sk-or-v1-…a1b2')
            ->assertDontSee($key);

        // Boş gönderim anahtarı korur, "sil" kaldırır.
        $this->actingAs($this->admin, 'admin')->post('/admin/settings', ['ai_api_key' => '', 'ai_fake_mode' => '1']);
        $this->assertSame($key, \App\Support\AiConfig::apiKey());
        $this->assertTrue(\App\Support\AiConfig::isFake());

        $this->actingAs($this->admin, 'admin')->post('/admin/settings', ['ai_api_key_clear' => '1']);
        $this->assertNull(\App\Support\AiConfig::apiKey());
    }

    public function test_default_models_are_cheapest_quality(): void
    {
        $this->assertSame('google/gemini-3.1-flash-lite-image', \App\Models\Setting::get('ai.image_model'));
        $this->assertSame('openai/gpt-5.4-nano', \App\Models\Setting::get('ai.text_model'));
    }

    public function test_settings_are_saved(): void
    {
        $this->actingAs($this->admin, 'admin')->post('/admin/settings', [
            'ai_image_model' => 'google/gemini-3-pro-image-preview',
            'credits_signup_bonus' => '15',
            'app_maintenance' => '1',
        ])->assertSessionHas('success');

        $this->assertSame('google/gemini-3-pro-image-preview', \App\Models\Setting::get('ai.image_model'));
        $this->assertSame(15, \App\Models\Setting::get('credits.signup_bonus'));
        $this->assertTrue(\App\Models\Setting::get('app.maintenance'));
    }
}
