<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('original_path');
            $table->unsignedInteger('original_width')->nullable();
            $table->unsignedInteger('original_height')->nullable();
            $table->string('cutout_path')->nullable();
            $table->string('cutout_status', 20)->default('pending');
            $table->text('cutout_error')->nullable();
            $table->foreignId('scene_type_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('shadow_enabled')->default(true);
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('user_prompt')->nullable();
            $table->longText('final_prompt');
            $table->foreignId('studio_style_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scene_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lighting_preset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quality_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('shadow_enabled')->default(true);
            $table->string('aspect_ratio', 8)->default('4:5');
            $table->unsignedTinyInteger('variant_count');
            $table->string('model_id');
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedInteger('credits_charged')->default(0);
            $table->unsignedInteger('credits_refunded')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('generation_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('variant_index');
            $table->string('status', 20)->default('pending');
            $table->string('path')->nullable();
            $table->string('thumb_path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('is_master')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->string('openrouter_generation_id')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('amount');
            $table->unsignedInteger('balance_after');
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generation_image_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 20);
            $table->string('model_id');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->text('error')->nullable();
            $table->json('request_meta')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_request_logs');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('generation_images');
        Schema::dropIfExists('generations');
        Schema::dropIfExists('projects');
    }
};
