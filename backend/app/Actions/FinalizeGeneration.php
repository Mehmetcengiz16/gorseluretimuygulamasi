<?php

namespace App\Actions;

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Enums\ProcessStatus;
use App\Models\Generation;
use App\Services\Credits\CreditService;
use Illuminate\Support\Facades\DB;

/**
 * Tüm varyantlar bittiğinde üretimi sonuçlandırır: durum, master seçimi, maliyet ve başarısızlar için kredi iadesi.
 * Her varyant işi bittikten sonra çağrılır; yalnızca son biten iş gerçekten sonuçlandırır.
 */
class FinalizeGeneration
{
    public function __construct(private readonly CreditService $credits) {}

    public function __invoke(Generation $generation): void
    {
        $refund = DB::transaction(function () use ($generation) {
            /** @var Generation $locked */
            $locked = Generation::query()->lockForUpdate()->find($generation->id);
            if (! $locked || $locked->status->isFinished()) {
                return 0;
            }

            $images = $locked->images()->get();
            $pending = $images->whereIn('status', [ProcessStatus::Pending, ProcessStatus::Processing]);
            if ($pending->isNotEmpty()) {
                return 0;
            }

            $done = $images->where('status', ProcessStatus::Done);
            $failedCount = $images->where('status', ProcessStatus::Failed)->count();

            if ($done->isNotEmpty() && ! $done->contains('is_master', true)) {
                $done->first()->update(['is_master' => true]);
            }

            // Başarısız varyantların kredisi orantılı iade edilir.
            $perImage = $locked->variant_count > 0 ? $locked->credits_charged / $locked->variant_count : 0;
            $refund = (int) floor($perImage * $failedCount) - $locked->credits_refunded;

            $locked->update([
                'status' => match (true) {
                    $done->isEmpty() => GenerationStatus::Failed,
                    $failedCount > 0 => GenerationStatus::Partial,
                    default => GenerationStatus::Completed,
                },
                'cost_usd' => (float) $images->sum('cost_usd'),
                'credits_refunded' => $locked->credits_refunded + max(0, $refund),
                'error_message' => $done->isEmpty() ? $images->pluck('error_message')->filter()->first() : null,
                'completed_at' => now(),
            ]);

            return max(0, $refund);
        });

        if ($refund > 0) {
            $generation->refresh();
            $this->credits->credit(
                $generation->user,
                $refund,
                CreditTransactionType::Refund,
                $generation,
                'Başarısız varyant iadesi (#'.$generation->id.')',
            );
        }
    }
}
