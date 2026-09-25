<?php

namespace App\Services\Credits;

use App\Enums\CreditTransactionType;
use App\Exceptions\ApiException;
use App\Models\Admin;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Kredi defteri. Bakiye yalnızca buradan değişir; her değişiklik bir credit_transactions satırıdır.
 */
class CreditService
{
    public function debit(User $user, int $amount, CreditTransactionType $type, ?Model $reference = null, ?string $description = null): CreditTransaction
    {
        return $this->apply($user, -abs($amount), $type, $reference, $description);
    }

    public function credit(User $user, int $amount, CreditTransactionType $type, ?Model $reference = null, ?string $description = null, ?Admin $admin = null): CreditTransaction
    {
        return $this->apply($user, abs($amount), $type, $reference, $description, $admin);
    }

    /** Admin düzeltmesi: pozitif ekler, negatif düşer (bakiye 0'ın altına inemez). */
    public function adjust(User $user, int $amount, string $description, Admin $admin): CreditTransaction
    {
        $type = $amount >= 0 ? CreditTransactionType::AdminGrant : CreditTransactionType::AdminDeduct;

        return $this->apply($user, $amount, $type, null, $description, $admin);
    }

    private function apply(User $user, int $amount, CreditTransactionType $type, ?Model $reference, ?string $description, ?Admin $admin = null): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $description, $admin) {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            $newBalance = $locked->credit_balance + $amount;
            if ($newBalance < 0) {
                throw ApiException::insufficientCredits();
            }

            $locked->forceFill(['credit_balance' => $newBalance])->save();
            $user->credit_balance = $newBalance;

            return CreditTransaction::create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'admin_id' => $admin?->id,
            ]);
        });
    }
}
