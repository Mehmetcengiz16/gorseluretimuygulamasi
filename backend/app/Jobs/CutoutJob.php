<?php

namespace App\Jobs;

use App\Enums\ProcessStatus;
use App\Models\Project;
use App\Models\Setting;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequestFailed;
use App\Services\ImageStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CutoutJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public Project $project, public bool $precise = false)
    {
        $this->onQueue('ai');
    }

    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(AiClient $ai, ImageStorage $storage): void
    {
        $project = $this->project->fresh();
        if (! $project) {
            return;
        }

        $project->update(['cutout_status' => ProcessStatus::Processing, 'cutout_error' => null]);

        $prompt = (string) Setting::get('ai.cutout_prompt');
        if ($this->precise) {
            $prompt .= ' Pay extra attention to fine edges, transparent glass, thin parts and reflections; keep edges razor sharp.';
        }

        $ratio = $this->ratioFor($project->original_width, $project->original_height);

        try {
            $result = $ai->generateImage(
                model: (string) Setting::get('ai.cutout_model'),
                systemPrompt: 'You are a precise product photo retoucher.',
                userPrompt: $prompt,
                inputImageBinary: $storage->read($project->original_path),
                inputMime: $storage->mime($project->original_path),
                meta: ['purpose' => 'cutout', 'user_id' => $project->user_id, 'aspect_ratio' => $ratio],
            );
        } catch (AiRequestFailed $e) {
            if (! $e->retryable) {
                $this->markFailed($project, $e->getMessage());
                $this->delete();

                return;
            }
            throw $e;
        }

        $path = $storage->storeBinary($result->binary, 'projects/'.$project->id.'/cutout-'.now()->timestamp.'.png');

        $project->update(['cutout_path' => $path, 'cutout_status' => ProcessStatus::Done]);
    }

    public function failed(?Throwable $e): void
    {
        $this->markFailed($this->project, $e?->getMessage() ?? 'Bilinmeyen hata');
    }

    private function markFailed(Project $project, string $error): void
    {
        $project->update(['cutout_status' => ProcessStatus::Failed, 'cutout_error' => mb_substr($error, 0, 1000)]);
    }

    private function ratioFor(?int $w, ?int $h): string
    {
        if (! $w || ! $h) {
            return '4:5';
        }
        $r = $w / $h;

        return match (true) {
            $r > 1.5 => '16:9',
            $r > 1.1 => '4:3',
            $r > 0.9 => '1:1',
            $r > 0.7 => '4:5',
            default => '9:16',
        };
    }
}
