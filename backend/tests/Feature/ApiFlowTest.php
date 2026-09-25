<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\Project;
use App\Models\QualityLevel;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_register_grants_signup_bonus_and_returns_token(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ayşe', 'email' => 'ayse@example.com', 'password' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('user.credit_balance', 10)
            ->assertJsonStructure(['token', 'user' => ['id', 'is_pro']]);

        $this->assertDatabaseHas('credit_transactions', ['type' => 'signup_bonus', 'amount' => 10]);
    }

    public function test_login_rejects_wrong_password_with_turkish_error(): void
    {
        User::factory()->create(['email' => 'a@b.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_catalog_hides_prompt_fragments(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/catalog')->assertOk();

        $this->assertCount(4, $response->json('data.lighting_presets'));
        $this->assertStringNotContainsString('prompt_fragment', $response->getContent());
    }

    public function test_full_generation_flow_charges_credits_and_completes(): void
    {
        $user = $this->userWithCredits(20);
        Sanctum::actingAs($user);

        $projectId = $this->postJson('/api/v1/projects', ['image' => UploadedFile::fake()->image('p.jpg', 600, 800)])
            ->assertCreated()
            ->json('data.id');

        // Kuyruk testte senkron: dekupe hemen biter.
        $this->getJson("/api/v1/projects/{$projectId}")->assertJsonPath('data.cutout_status', 'done');

        $response = $this->postJson("/api/v1/projects/{$projectId}/generations", [
            'user_prompt' => 'Siyah mermer',
            'quality_level_id' => QualityLevel::where('key', 'ultra')->value('id'),
            'variant_count' => 4,
        ])->assertStatus(202);

        $generation = Generation::findOrFail($response->json('data.id'));
        $this->assertSame(GenerationStatus::Completed, $generation->status);
        $this->assertSame(16, $user->fresh()->credit_balance);
        $this->assertSame(1, $generation->images()->where('is_master', true)->count());

        $this->getJson("/api/v1/generations/{$generation->id}")
            ->assertOk()
            ->assertJsonPath('data.ready_count', 4)
            ->assertJsonPath('data.project.id', $projectId);
    }

    public function test_edit_tools_create_new_variant_from_selected_image(): void
    {
        $user = $this->userWithCredits(10);
        Sanctum::actingAs($user);

        $projectId = $this->postJson('/api/v1/projects', ['image' => UploadedFile::fake()->image('p.jpg', 600, 800)])->json('data.id');
        $generationId = $this->postJson("/api/v1/projects/{$projectId}/generations", [
            'quality_level_id' => QualityLevel::where('key', 'ultra')->value('id'),
            'variant_count' => 2,
        ])->json('data.id');
        $source = Generation::find($generationId)->images()->first();

        foreach ([['color', 'warm', 1], ['ratio', '16:9', 1], ['upscale', '4k', 1], ['light', 'left', 1], ['retouch', 'clean', 1]] as [$tool, $option]) {
            $this->postJson("/api/v1/generation-images/{$source->id}/edit", ['tool' => $tool, 'option' => $option, 'note' => 'test'])
                ->assertStatus(202);
        }

        $generation = Generation::find($generationId);
        $edited = $generation->images()->where('edit_tool', 'ratio')->first();

        $this->assertSame(7, $generation->variant_count);
        $this->assertSame(GenerationStatus::Completed, $generation->status);
        $this->assertSame(3, $user->fresh()->credit_balance); // 10 - 2 - 5
        $this->assertSame('16:9', $edited->aspect_ratio);
        $this->assertGreaterThan($edited->height, $edited->width);
        $this->assertSame($source->id, $edited->source_image_id);

        $this->getJson("/api/v1/generations/{$generationId}")->assertJsonPath('data.images.2.edit_label', 'Renk Sıcaklığı · Sıcak');

        $this->postJson("/api/v1/generation-images/{$source->id}/edit", ['tool' => 'color', 'option' => 'purple'])->assertStatus(422);
    }

    public function test_insufficient_credits_returns_402(): void
    {
        $user = $this->userWithCredits(1);
        Sanctum::actingAs($user);
        $project = $this->projectFor($user);

        $this->postJson("/api/v1/projects/{$project->id}/generations", [
            'quality_level_id' => QualityLevel::first()->id,
            'variant_count' => 4,
        ])->assertStatus(402)->assertJsonPath('code', 'INSUFFICIENT_CREDITS');

        $this->assertSame(1, $user->fresh()->credit_balance);
        $this->assertDatabaseCount('generations', 0);
    }

    public function test_free_user_cannot_use_pro_template_or_eight_variants(): void
    {
        $user = $this->userWithCredits(50);
        Sanctum::actingAs($user);
        $project = $this->projectFor($user);
        $quality = QualityLevel::first()->id;

        $this->postJson("/api/v1/projects/{$project->id}/generations", ['quality_level_id' => $quality, 'variant_count' => 8])
            ->assertStatus(403)->assertJsonPath('code', 'PRO_REQUIRED');

        $this->postJson("/api/v1/projects/{$project->id}/generations", [
            'quality_level_id' => $quality,
            'variant_count' => 1,
            'template_id' => Template::where('is_pro', true)->value('id'),
        ])->assertStatus(403);
    }

    public function test_users_cannot_see_each_others_projects(): void
    {
        $project = $this->projectFor(User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/projects/{$project->id}")->assertNotFound()->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_template_like_toggles(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $template = Template::first();
        $likes = $template->likes_count;

        $this->postJson("/api/v1/templates/{$template->id}/like")->assertJsonPath('data.liked', true)->assertJsonPath('data.likes_count', $likes + 1);
        $this->postJson("/api/v1/templates/{$template->id}/like")->assertJsonPath('data.liked', false)->assertJsonPath('data.likes_count', $likes);
    }

    public function test_disabled_user_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_active' => false]));

        $this->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('code', 'ACCOUNT_DISABLED');
    }

    private function userWithCredits(int $credits): User
    {
        $user = User::factory()->create();
        $user->forceFill(['credit_balance' => $credits])->save();

        return $user;
    }

    private function projectFor(User $user): Project
    {
        Storage::disk('public')->put('projects/test.jpg', UploadedFile::fake()->image('p.jpg')->getContent());

        return $user->projects()->create(['original_path' => 'projects/test.jpg', 'cutout_status' => 'done']);
    }
}
