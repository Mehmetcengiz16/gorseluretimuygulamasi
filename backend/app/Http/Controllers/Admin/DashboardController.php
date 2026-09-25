<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GenerationStatus;
use App\Http\Controllers\Controller;
use App\Models\AiRequestLog;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();
        $finishedToday = Generation::whereDate('created_at', $today)->whereIn('status', ['completed', 'partial', 'failed']);
        $finishedCount = (clone $finishedToday)->count();
        $successCount = (clone $finishedToday)->whereIn('status', ['completed', 'partial'])->count();

        $stats = [
            'users' => User::count(),
            'users_today' => User::whereDate('created_at', $today)->count(),
            'generations_today' => Generation::whereDate('created_at', $today)->count(),
            'success_rate' => $finishedCount ? round($successCount / $finishedCount * 100) : null,
            'cost_today' => (float) AiRequestLog::whereDate('created_at', $today)->sum('cost_usd'),
            'cost_month' => (float) AiRequestLog::where('created_at', '>=', $today->copy()->startOfMonth())->sum('cost_usd'),
            'queued' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        $from = $today->copy()->subDays(29);
        $daily = Generation::selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('created_at', '>=', $from)->groupBy('d')->pluck('c', 'd');
        $dailyCost = AiRequestLog::selectRaw('DATE(created_at) as d, SUM(cost_usd) as c')
            ->where('created_at', '>=', $from)->groupBy('d')->pluck('c', 'd');

        $chart = collect(range(0, 29))->map(function ($i) use ($from, $daily, $dailyCost) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();

            return ['label' => $day->translatedFormat('d M'), 'count' => (int) ($daily[$key] ?? 0), 'cost' => (float) ($dailyCost[$key] ?? 0)];
        });

        return view('admin.dashboard', [
            'stats' => $stats,
            'chart' => $chart,
            'recent' => Generation::with(['user', 'masterImage', 'project'])->latest('id')->limit(10)->get(),
            'failed' => Generation::with('user')->where('status', GenerationStatus::Failed)->latest('id')->limit(5)->get(),
        ]);
    }
}
