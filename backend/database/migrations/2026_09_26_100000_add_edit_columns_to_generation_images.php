<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sonuç ekranı araçları (rötuş, ışık, oran, renk, 4K): düzenleme mevcut varyanttan yeni varyant üretir. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generation_images', function (Blueprint $table) {
            $table->foreignId('source_image_id')->nullable()->after('generation_id')->constrained('generation_images')->nullOnDelete();
            $table->string('edit_tool', 20)->nullable()->after('source_image_id');
            $table->string('edit_option', 20)->nullable()->after('edit_tool');
            $table->text('edit_prompt')->nullable()->after('edit_option');
            $table->string('aspect_ratio', 8)->nullable()->after('edit_prompt');
        });
    }

    public function down(): void
    {
        Schema::table('generation_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_image_id');
            $table->dropColumn(['edit_tool', 'edit_option', 'edit_prompt', 'aspect_ratio']);
        });
    }
};
