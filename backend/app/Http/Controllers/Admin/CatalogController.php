<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LightingPreset;
use App\Models\QualityLevel;
use App\Models\SceneType;
use App\Models\StudioStyle;
use App\Models\Template;
use App\Services\Ai\PromptBuilder;
use App\Services\ImageStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Katalog kaynakları için tek, yapılandırmaya dayalı CRUD.
 * Her kaynak: model, başlıklar, liste sütunları ve form alanları.
 */
class CatalogController extends Controller
{
    public function __construct(private readonly ImageStorage $storage) {}

    private function resources(): array
    {
        return [
            'categories' => [
                'model' => Category::class, 'title' => 'Kategoriler', 'singular' => 'Kategori', 'icon' => 'category',
                'columns' => ['icon' => 'İkon', 'name' => 'Ad', 'slug' => 'Slug', 'sort_order' => 'Sıra', 'is_active' => 'Aktif'],
                'fields' => [
                    'name' => ['Ad', 'text', 'required|string|max:100'],
                    'slug' => ['Slug', 'text', 'nullable|string|max:100'],
                    'icon' => ['Material Symbols ikon adı', 'icon', 'nullable|string|max:60', 'ör. spa, fragrance, diamond'],
                    'sort_order' => ['Sıra', 'number', 'nullable|integer|min:0'],
                    'is_active' => ['Aktif', 'checkbox', 'boolean'],
                ],
            ],
            'studio-styles' => [
                'model' => StudioStyle::class, 'title' => 'Stüdyo Stilleri', 'singular' => 'Stil', 'icon' => 'palette',
                'columns' => ['thumbnail_path' => 'Görsel', 'name' => 'Ad', 'is_pro' => 'PRO', 'sort_order' => 'Sıra', 'is_active' => 'Aktif'],
                'fields' => [
                    'name' => ['Ad', 'text', 'required|string|max:100'],
                    'slug' => ['Slug', 'text', 'nullable|string|max:100'],
                    'thumbnail_path' => ['Küçük resim', 'image', 'nullable|image|max:8192'],
                    'prompt_fragment' => ['Prompt parçası (İngilizce, kullanıcıya görünmez)', 'textarea', 'nullable|string|max:2000'],
                    'is_pro' => ['PRO', 'checkbox', 'boolean'],
                    'sort_order' => ['Sıra', 'number', 'nullable|integer|min:0'],
                    'is_active' => ['Aktif', 'checkbox', 'boolean'],
                ],
            ],
            'scene-types' => [
                'model' => SceneType::class, 'title' => 'Sahne Türleri', 'singular' => 'Sahne', 'icon' => 'landscape',
                'columns' => ['thumbnail_path' => 'Görsel', 'name' => 'Ad', 'is_pro' => 'PRO', 'sort_order' => 'Sıra', 'is_active' => 'Aktif'],
                'fields' => [
                    'name' => ['Ad', 'text', 'required|string|max:100'],
                    'slug' => ['Slug', 'text', 'nullable|string|max:100'],
                    'thumbnail_path' => ['Küçük resim', 'image', 'nullable|image|max:8192'],
                    'prompt_fragment' => ['Prompt parçası', 'textarea', 'nullable|string|max:2000'],
                    'is_pro' => ['PRO', 'checkbox', 'boolean'],
                    'sort_order' => ['Sıra', 'number', 'nullable|integer|min:0'],
                    'is_active' => ['Aktif', 'checkbox', 'boolean'],
                ],
            ],
            'lighting-presets' => [
                'model' => LightingPreset::class, 'title' => 'Işık Ön Ayarları', 'singular' => 'Işık', 'icon' => 'light_mode',
                'columns' => ['icon' => 'İkon', 'name' => 'Ad', 'subtitle' => 'Alt başlık', 'is_pro' => 'PRO', 'is_active' => 'Aktif'],
                'fields' => [
                    'name' => ['Ad', 'text', 'required|string|max:100'],
                    'slug' => ['Slug', 'text', 'nullable|string|max:100'],
                    'subtitle' => ['Alt başlık', 'text', 'nullable|string|max:100'],
                    'icon' => ['Material Symbols ikon adı', 'icon', 'nullable|string|max:60', 'ör. wb_twilight, highlight, flare'],
                    'prompt_fragment' => ['Prompt parçası', 'textarea', 'nullable|string|max:2000'],
                    'is_pro' => ['PRO', 'checkbox', 'boolean'],
                    'sort_order' => ['Sıra', 'number', 'nullable|integer|min:0'],
                    'is_active' => ['Aktif', 'checkbox', 'boolean'],
                ],
            ],
            'quality-levels' => [
                'model' => QualityLevel::class, 'title' => 'Kalite Seviyeleri', 'singular' => 'Kalite', 'icon' => 'high_quality',
                'columns' => ['key' => 'Anahtar', 'name' => 'Ad', 'credit_multiplier' => 'Kredi çarpanı', 'image_size' => 'Çözünürlük', 'model_id' => 'Özel model'],
                'fields' => [
                    'key' => ['Anahtar', 'text', 'required|string|max:30'],
                    'name' => ['Ad', 'text', 'required|string|max:100'],
                    'credit_multiplier' => ['Kredi çarpanı', 'number', 'required|numeric|min:0.1|max:20', 'Varyant başına kredi = 1 × çarpan'],
                    'image_size' => ['Çözünürlük', 'select', 'required|in:1K,2K,4K', null, ['1K' => '1K', '2K' => '2K', '4K' => '4K']],
                    'model_id' => ['Özel model (boşsa genel ayar)', 'text', 'nullable|string|max:150'],
                    'prompt_fragment' => ['Prompt parçası', 'textarea', 'nullable|string|max:2000'],
                    'is_pro' => ['PRO', 'checkbox', 'boolean'],
                    'sort_order' => ['Sıra', 'number', 'nullable|integer|min:0'],
                ],
            ],
            'templates' => [
                'model' => Template::class, 'title' => 'Şablonlar', 'singular' => 'Şablon', 'icon' => 'dashboard_customize',
                'columns' => ['cover_path' => 'Kapak', 'title' => 'Başlık', 'category.name' => 'Kategori', 'badge' => 'Rozet', 'likes_count' => 'Beğeni', 'uses_count' => 'Kullanım', 'is_active' => 'Aktif'],
                'fields' => [
                    'title' => ['Başlık', 'text', 'required|string|max:120'],
                    'subtitle' => ['Alt başlık', 'text', 'nullable|string|max:160'],
                    'cover_path' => ['Kapak görseli (4:5)', 'image', 'nullable|image|max:8192'],
                    'category_id' => ['Kategori', 'select', 'nullable|exists:categories,id', null, fn () => Category::orderBy('sort_order')->pluck('name', 'id')->all()],
                    'badge' => ['Rozet', 'select', 'nullable|in:pro,trend,new', null, ['' => '—'] + Template::BADGES],
                    'studio_style_id' => ['Stüdyo stili', 'select', 'nullable|exists:studio_styles,id', null, fn () => StudioStyle::orderBy('sort_order')->pluck('name', 'id')->all()],
                    'scene_type_id' => ['Sahne türü', 'select', 'nullable|exists:scene_types,id', null, fn () => SceneType::orderBy('sort_order')->pluck('name', 'id')->all()],
                    'lighting_preset_id' => ['Işık', 'select', 'nullable|exists:lighting_presets,id', null, fn () => LightingPreset::orderBy('sort_order')->pluck('name', 'id')->all()],
                    'quality_key' => ['Varsayılan kalite', 'select', 'nullable|string', null, fn () => ['' => '—'] + QualityLevel::orderBy('sort_order')->pluck('name', 'key')->all()],
                    'default_prompt' => ['Varsayılan prompt (Türkçe, kullanıcıya görünür)', 'textarea', 'nullable|string|max:500'],
                    'likes_count' => ['Beğeni sayısı', 'number', 'nullable|integer|min:0'],
                    'is_pro' => ['PRO', 'checkbox', 'boolean'],
                    'is_featured' => ['Öne çıkan', 'checkbox', 'boolean'],
                    'sort_order' => ['Sıra', 'number', 'nullable|integer|min:0'],
                    'is_active' => ['Aktif', 'checkbox', 'boolean'],
                ],
            ],
        ];
    }

    public function index(Request $request, string $resource): View
    {
        $config = $this->config($resource);
        $query = $config['model']::query();

        if ($resource === 'templates') {
            $query->with('category');
        }
        if ($request->filled('q')) {
            $column = array_key_exists('title', $config['fields']) ? 'title' : 'name';
            $query->where($column, 'like', '%'.$request->q.'%');
        }

        return view('admin.catalog.index', [
            'resource' => $resource,
            'config' => $config,
            'items' => $query->orderBy('sort_order')->orderBy('id')->paginate(30)->withQueryString(),
        ]);
    }

    public function create(string $resource): View
    {
        $config = $this->config($resource);

        return $this->form($resource, $config, new $config['model']);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $config = $this->config($resource);
        $item = new $config['model'];
        $this->fill($request, $config, $item, $resource);

        return redirect()->route('admin.catalog.index', $resource)->with('success', $config['singular'].' oluşturuldu.');
    }

    public function edit(string $resource, int $id): View
    {
        $config = $this->config($resource);

        return $this->form($resource, $config, $config['model']::findOrFail($id));
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource);
        $this->fill($request, $config, $config['model']::findOrFail($id), $resource);

        return redirect()->route('admin.catalog.index', $resource)->with('success', $config['singular'].' güncellendi.');
    }

    public function destroy(string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource);
        $config['model']::findOrFail($id)->delete();

        return back()->with('success', $config['singular'].' silindi.');
    }

    /** Şablon için PromptBuilder çıktısını önizler. */
    public function preview(string $resource, int $id, PromptBuilder $prompts): View
    {
        abort_unless($resource === 'templates', 404);
        $template = Template::with(['studioStyle', 'sceneType', 'lightingPreset'])->findOrFail($id);
        $quality = QualityLevel::where('key', $template->quality_key)->first();

        return view('admin.catalog.preview', [
            'template' => $template,
            'system' => $prompts->systemPrompt(),
            'prompt' => $prompts->forVariant(
                $prompts->build($template->sceneType, $template->studioStyle, $template->lightingPreset, $quality, true, $template->default_prompt, '4:5'),
                1
            ),
        ]);
    }

    private function form(string $resource, array $config, Model $item): View
    {
        $fields = collect($config['fields'])->map(function ($f) {
            if (isset($f[4]) && $f[4] instanceof \Closure) {
                $f[4] = ($f[4])();
            }

            return $f;
        })->all();

        return view('admin.catalog.form', compact('resource', 'config', 'item', 'fields'));
    }

    private function fill(Request $request, array $config, Model $item, string $resource): void
    {
        $rules = collect($config['fields'])->mapWithKeys(fn ($f, $name) => [$name => $f[2]])->all();
        if (isset($rules['slug'])) {
            $table = $item->getTable();
            $rules['slug'] .= '|unique:'.$table.',slug'.($item->exists ? ','.$item->id : '');
        }
        if ($resource === 'quality-levels') {
            $rules['key'] .= '|unique:quality_levels,key'.($item->exists ? ','.$item->id : '');
        }

        $data = $request->validate($rules);

        foreach ($config['fields'] as $name => $field) {
            $type = $field[1];
            if ($type === 'checkbox') {
                $item->{$name} = $request->boolean($name);
            } elseif ($type === 'image') {
                if ($request->hasFile($name)) {
                    $item->{$name} = $this->storage->storeCatalog($request->file($name), $resource);
                } elseif ($request->filled($name.'_url')) {
                    $item->{$name} = $request->input($name.'_url');
                }
            } elseif ($type === 'number') {
                $item->{$name} = $data[$name] ?? 0;
            } else {
                $item->{$name} = ($data[$name] ?? null) === '' ? null : ($data[$name] ?? null);
            }
        }

        if (array_key_exists('slug', $config['fields']) && blank($item->slug)) {
            $item->slug = Str::slug($item->name ?? $item->title ?? Str::random(6));
        }

        $item->save();
    }

    private function config(string $resource): array
    {
        $resources = $this->resources();
        abort_unless(isset($resources[$resource]), 404);

        return $resources[$resource];
    }

    /** Menü için kaynak listesi. */
    public static function menu(): array
    {
        return collect((new self(app(ImageStorage::class)))->resources())
            ->map(fn ($r) => ['title' => $r['title'], 'icon' => $r['icon']])
            ->all();
    }
}
