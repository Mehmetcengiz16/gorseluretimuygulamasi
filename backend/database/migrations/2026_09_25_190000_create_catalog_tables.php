<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('studio_styles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('thumbnail_path')->nullable();
            $table->text('prompt_fragment')->nullable();
            $table->boolean('is_pro')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scene_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('thumbnail_path')->nullable();
            $table->text('prompt_fragment')->nullable();
            $table->boolean('is_pro')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lighting_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->string('icon')->nullable();
            $table->text('prompt_fragment')->nullable();
            $table->boolean('is_pro')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('quality_levels', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->decimal('credit_multiplier', 4, 2)->default(1);
            $table->string('image_size', 8)->default('1K');
            $table->string('model_id')->nullable();
            $table->text('prompt_fragment')->nullable();
            $table->boolean('is_pro')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('cover_path')->nullable();
            $table->string('badge', 10)->nullable();
            $table->foreignId('studio_style_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scene_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lighting_preset_id')->nullable()->constrained()->nullOnDelete();
            $table->text('default_prompt')->nullable();
            $table->string('quality_key')->nullable();
            $table->boolean('is_pro')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('uses_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('template_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'template_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('template_likes');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('quality_levels');
        Schema::dropIfExists('lighting_presets');
        Schema::dropIfExists('scene_types');
        Schema::dropIfExists('studio_styles');
        Schema::dropIfExists('categories');
    }
};
