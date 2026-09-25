<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogItemResource;
use App\Http\Resources\TemplateResource;
use App\Models\Category;
use App\Models\LightingPreset;
use App\Models\QualityLevel;
use App\Models\SceneType;
use App\Models\Setting;
use App\Models\StudioStyle;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function config(): JsonResponse
    {
        return response()->json(['data' => [
            'min_version' => (string) Setting::get('app.min_version'),
            'maintenance' => (bool) Setting::get('app.maintenance'),
            'variant_options' => [1, 2, 4, 8],
            'max_variants_free' => (int) Setting::get('limits.max_variants_free'),
            'aspect_ratios' => ['1:1', '4:5', '3:4', '9:16', '16:9'],
            'prompt_max_length' => 500,
            'model_label' => (string) Setting::get('ai.model_label'),
        ]]);
    }

    public function catalog(): JsonResponse
    {
        return response()->json(['data' => [
            'categories' => CatalogItemResource::collection(Category::active()->get()),
            'studio_styles' => CatalogItemResource::collection(StudioStyle::active()->get()),
            'scene_types' => CatalogItemResource::collection(SceneType::active()->get()),
            'lighting_presets' => CatalogItemResource::collection(LightingPreset::active()->get()),
            'quality_levels' => CatalogItemResource::collection(QualityLevel::orderBy('sort_order')->get()),
        ]]);
    }

    public function templates(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $query = Template::active()
            ->with('category')
            ->withExists(['likers as liked' => fn ($q) => $q->where('users.id', $userId)])
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->string('category'))))
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)
                    ->orWhere('subtitle', 'like', $term)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $term)));
            });

        return TemplateResource::collection($query->paginate(20));
    }

    public function template(Request $request, Template $template): TemplateResource
    {
        abort_unless($template->is_active, 404);

        $template->load('category');
        $template->liked = $template->likers()->where('users.id', $request->user()->id)->exists();

        return new TemplateResource($template);
    }

    public function toggleLike(Request $request, Template $template): JsonResponse
    {
        $user = $request->user();

        $liked = DB::transaction(function () use ($user, $template) {
            $changes = $user->likedTemplates()->toggle($template->id);
            $liked = ! empty($changes['attached']);
            $liked ? $template->increment('likes_count') : $template->decrement('likes_count');

            return $liked;
        });

        return response()->json(['data' => ['liked' => $liked, 'likes_count' => max(0, $template->fresh()->likes_count)]]);
    }
}
