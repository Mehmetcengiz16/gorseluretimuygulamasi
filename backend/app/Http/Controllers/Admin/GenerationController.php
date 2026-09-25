<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Enums\ProcessStatus;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateVariantJob;
use App\Models\Generation;
use App\Services\Credits\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenerationController extends Controller
{
    public function index(Request $request): View
    {
        $generations = Generation::query()
            ->with(['user', 'project', 'masterImage', 'studioStyle', 'qualityLevel'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->user))
            ->latest('id')
            ->paginate(24)
            ->withQueryString();

        return view('admin.generations.index', [
            'generations' => $generations,
            'statuses' => GenerationStatus::cases(),
        ]);
    }

    public function show(Generation $generation): View
    {
        $generation->load(['user', 'project', 'images.source', 'studioStyle', 'sceneType', 'lightingPreset', 'qualityLevel', 'template']);

        return view('admin.generations.show', compact('generation'));
    }

    /** Başarısız varyantları tekrar kuyruğa alır (kredi yeniden düşülmez; önceki iade geri alınır). */
    public function retry(Request $request, Generation $generation, CreditService $credits): RedirectResponse
    {
        $failed = $generation->images()->where('status', ProcessStatus::Failed)->get();
        if ($failed->isEmpty()) {
            return back()->with('error', 'Tekrar denenecek başarısız varyant yok.');
        }

        // Başarısız varyantlar için yapılmış iade varsa, tekrar denemeden önce geri alınır.
        if ($generation->credits_refunded > 0) {
            $perImage = $generation->credits_charged / max(1, $generation->variant_count);
            $reclaim = min($generation->credits_refunded, (int) floor($perImage * $failed->count()));
            if ($reclaim > 0 && $generation->user->credit_balance >= $reclaim) {
                $credits->debit($generation->user, $reclaim, CreditTransactionType::Generation, $generation, 'Admin tekrar denemesi (#'.$generation->id.')');
                $generation->decrement('credits_refunded', $reclaim);
            }
        }

        $generation->update(['status' => GenerationStatus::Processing, 'completed_at' => null, 'error_message' => null]);
        foreach ($failed as $image) {
            $image->update(['status' => ProcessStatus::Pending, 'error_message' => null]);
            GenerateVariantJob::dispatch($image);
        }

        return back()->with('success', $failed->count().' varyant tekrar kuyruğa alındı.');
    }

    public function refund(Request $request, Generation $generation, CreditService $credits): RedirectResponse
    {
        abort_unless($request->user('admin')->isSuperAdmin(), 403);

        $refundable = $generation->credits_charged - $generation->credits_refunded;
        if ($refundable <= 0) {
            return back()->with('error', 'İade edilecek kredi kalmadı.');
        }

        $credits->credit($generation->user, $refundable, CreditTransactionType::Refund, $generation, 'Admin iadesi (#'.$generation->id.')', $request->user('admin'));
        $generation->increment('credits_refunded', $refundable);

        return back()->with('success', $refundable.' kredi iade edildi.');
    }
}
