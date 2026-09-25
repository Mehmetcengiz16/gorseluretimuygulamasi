<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProcessStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Jobs\CutoutJob;
use App\Models\Project;
use App\Models\Template;
use App\Services\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    private const IMAGE_RULES = ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:15360'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $request->user()->projects()
            ->with(['latestGeneration.masterImage'])
            ->latest('id')
            ->paginate(20);

        return ProjectResource::collection($projects);
    }

    public function store(Request $request, ImageStorage $storage): JsonResponse
    {
        $data = $request->validate([
            'image' => self::IMAGE_RULES,
            'title' => ['nullable', 'string', 'max:120'],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
            'auto_cutout' => ['nullable', 'boolean'],
        ]);

        $template = isset($data['template_id']) ? Template::find($data['template_id']) : null;

        $project = $request->user()->projects()->create([
            'title' => $data['title'] ?? null,
            'original_path' => 'pending',
            'template_id' => $template?->id,
            'scene_type_id' => $template?->scene_type_id,
            'cutout_status' => ProcessStatus::Pending,
        ]);

        $stored = $storage->storeUpload($request->file('image'), 'projects/'.$project->id);
        $project->update([
            'original_path' => $stored['path'],
            'original_width' => $stored['width'],
            'original_height' => $stored['height'],
        ]);

        if ($request->boolean('auto_cutout', true)) {
            $this->queueCutout($project);
        }

        return (new ProjectResource($project->load('template')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Project $project): ProjectResource
    {
        $this->authorizeOwner($request, $project);

        return new ProjectResource($project->load([
            'template',
            'generations' => fn ($q) => $q->latest('id')->with('images'),
        ]));
    }

    public function update(Request $request, Project $project): ProjectResource
    {
        $this->authorizeOwner($request, $project);

        $project->update($request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'scene_type_id' => ['sometimes', 'nullable', 'integer', 'exists:scene_types,id'],
            'shadow_enabled' => ['sometimes', 'boolean'],
        ]));

        return new ProjectResource($project);
    }

    /** "Değiştir" / "Yeniden Kırp": fotoğrafı yeniler, dekupeyi sıfırlar. */
    public function replaceImage(Request $request, Project $project, ImageStorage $storage): ProjectResource
    {
        $this->authorizeOwner($request, $project);
        $request->validate(['image' => self::IMAGE_RULES]);

        $stored = $storage->storeUpload($request->file('image'), 'projects/'.$project->id);
        $project->update([
            'original_path' => $stored['path'],
            'original_width' => $stored['width'],
            'original_height' => $stored['height'],
            'cutout_path' => null,
            'cutout_error' => null,
        ]);

        $this->queueCutout($project);

        return new ProjectResource($project);
    }

    /** Dekupeyi başlatır; precise=true "Kenar Düzelt" içindir. */
    public function cutout(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request, $project);

        if ($project->cutout_status !== ProcessStatus::Processing) {
            $this->queueCutout($project, $request->boolean('precise'));
        }

        return (new ProjectResource($project))->response()->setStatusCode(202);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request, $project);
        $project->delete();

        return response()->json(['message' => 'Proje silindi.']);
    }

    private function queueCutout(Project $project, bool $precise = false): void
    {
        $project->update(['cutout_status' => ProcessStatus::Pending, 'cutout_error' => null]);
        CutoutJob::dispatch($project, $precise);
    }

    private function authorizeOwner(Request $request, Project $project): void
    {
        abort_unless($project->user_id === $request->user()->id, 404);
    }
}
