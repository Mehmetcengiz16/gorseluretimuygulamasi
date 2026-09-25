<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Credits\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount('generations')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('email', 'like', '%'.$request->q.'%')))
            ->when($request->filter === 'pro', fn ($q) => $q->where('pro_expires_at', '>', now()))
            ->when($request->filter === 'disabled', fn ($q) => $q->where('is_active', false))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user,
            'projects' => $user->projects()->withCount('generations')->latest('id')->limit(12)->get(),
            'transactions' => $user->creditTransactions()->with('admin')->latest('id')->paginate(20),
        ]);
    }

    public function adjustCredits(Request $request, User $user, CreditService $credits): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'description' => ['required', 'string', 'max:190'],
        ]);

        try {
            $credits->adjust($user, (int) $data['amount'], $data['description'], $request->user('admin'));
        } catch (ApiException) {
            return back()->with('error', 'Bakiye sıfırın altına düşürülemez.');
        }

        return back()->with('success', 'Kredi güncellendi. Yeni bakiye: '.$user->fresh()->credit_balance);
    }

    public function setPro(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validate(['pro_expires_at' => ['nullable', 'date']]);

        $user->update(['pro_expires_at' => $data['pro_expires_at'] ? Carbon::parse($data['pro_expires_at'])->endOfDay() : null]);

        return back()->with('success', $user->isPro() ? 'PRO üyelik tanımlandı.' : 'PRO üyelik kaldırıldı.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return back()->with('success', $user->is_active ? 'Hesap aktifleştirildi.' : 'Hesap engellendi ve oturumları kapatıldı.');
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user('admin')->isSuperAdmin(), 403, 'Bu işlem için süper admin yetkisi gerekir.');
    }
}
