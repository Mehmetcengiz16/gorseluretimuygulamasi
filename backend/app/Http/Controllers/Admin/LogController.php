<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CreditTransactionType;
use App\Http\Controllers\Controller;
use App\Models\AiRequestLog;
use App\Models\CreditTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LogController extends Controller
{
    public function aiRequests(Request $request): View
    {
        $logs = AiRequestLog::with('user')
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->purpose))
            ->when($request->boolean('errors'), fn ($q) => $q->whereNotNull('error'))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.logs.ai', compact('logs'));
    }

    public function creditTransactions(Request $request): View
    {
        $transactions = CreditTransaction::with(['user', 'admin'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.logs.credits', ['transactions' => $transactions, 'types' => CreditTransactionType::cases()]);
    }

    public function failedJobs(): View
    {
        $jobs = DB::table('failed_jobs')->latest('id')->paginate(30);

        return view('admin.logs.failed-jobs', compact('jobs'));
    }

    public function retryFailedJob(string $uuid): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', 'İş tekrar kuyruğa alındı.');
    }

    public function forgetFailedJob(string $uuid): RedirectResponse
    {
        Artisan::call('queue:forget', ['id' => $uuid]);

        return back()->with('success', 'İş kaydı silindi.');
    }
}
