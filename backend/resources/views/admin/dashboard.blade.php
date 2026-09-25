@extends('admin.layout')

@section('title', 'Dashboard')
@section('subtitle', now()->translatedFormat('d F Y, l'))

@section('content')
    <div class="grid grid-4">
        <div class="stat">
            <div class="stat-label"><span class="ms">group</span>Toplam Kullanıcı</div>
            <div class="stat-value">{{ number_format($stats['users'], 0, ',', '.') }}</div>
            <div class="stat-sub">Bugün +{{ $stats['users_today'] }} yeni kayıt</div>
        </div>
        <div class="stat">
            <div class="stat-label"><span class="ms">auto_awesome</span>Bugünkü Üretim</div>
            <div class="stat-value">{{ $stats['generations_today'] }}</div>
            <div class="stat-sub">Başarı oranı: {{ $stats['success_rate'] !== null ? '%'.$stats['success_rate'] : '—' }}</div>
        </div>
        <div class="stat">
            <div class="stat-label"><span class="ms">payments</span>AI Maliyeti</div>
            <div class="stat-value">${{ number_format($stats['cost_today'], 2) }}</div>
            <div class="stat-sub">Bu ay ${{ number_format($stats['cost_month'], 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-label"><span class="ms">stacks</span>Kuyruk</div>
            <div class="stat-value">{{ $stats['queued'] }}</div>
            <div class="stat-sub">
                @if ($stats['failed_jobs'])
                    <a href="{{ route('admin.logs.failed') }}" class="error-text">{{ $stats['failed_jobs'] }} başarısız iş</a>
                @else
                    Başarısız iş yok
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-2">
        @php $maxCount = max(1, $chart->max('count')); $maxCost = max(0.0001, $chart->max('cost')); @endphp
        <div class="card">
            <div class="card-head">
                <div class="card-title"><span class="ms">bar_chart</span>Son 30 Gün Üretim</div>
                <span class="badge badge-amber">{{ $chart->sum('count') }} toplam</span>
            </div>
            <div class="bars">
                @foreach ($chart as $day)
                    <div class="bar"><span style="height: {{ $day['count'] / $maxCount * 100 }}%"></span><div class="tip">{{ $day['label'] }} · {{ $day['count'] }}</div></div>
                @endforeach
            </div>
            <div class="bars-axis"><span>{{ $chart->first()['label'] }}</span><span>{{ $chart->last()['label'] }}</span></div>
        </div>
        <div class="card">
            <div class="card-head">
                <div class="card-title"><span class="ms">payments</span>Son 30 Gün Maliyet (USD)</div>
                <span class="badge badge-info">${{ number_format($chart->sum('cost'), 2) }}</span>
            </div>
            <div class="bars cost">
                @foreach ($chart as $day)
                    <div class="bar"><span style="height: {{ $day['cost'] / $maxCost * 100 }}%"></span><div class="tip">{{ $day['label'] }} · ${{ number_format($day['cost'], 3) }}</div></div>
                @endforeach
            </div>
            <div class="bars-axis"><span>{{ $chart->first()['label'] }}</span><span>{{ $chart->last()['label'] }}</span></div>
        </div>
    </div>

    <div class="grid grid-sidebar">
        <div class="card">
            <div class="card-head">
                <div class="card-title"><span class="ms">photo_library</span>Son Üretimler</div>
                <a href="{{ route('admin.generations.index') }}" class="label-md">Tümünü Gör</a>
            </div>
            @if ($recent->isEmpty())
                <div class="empty"><span class="ms">image</span>Henüz üretim yok</div>
            @else
                <div class="gallery">
                    @foreach ($recent as $g)
                        @include('admin.generations.card', ['g' => $g])
                    @endforeach
                </div>
            @endif
        </div>
        <div class="card">
            <div class="card-head">
                <div class="card-title"><span class="ms">error</span>Son Başarısızlar</div>
            </div>
            @forelse ($failed as $g)
                <a href="{{ route('admin.generations.show', $g) }}" class="row between" style="padding:10px 0;border-bottom:1px solid var(--border);color:inherit">
                    <div style="min-width:0">
                        <div class="title-md">#{{ $g->id }} · {{ $g->user?->name }}</div>
                        <div class="body-sm error-text truncate" style="max-width:260px">{{ $g->error_message ?: 'Hata mesajı yok' }}</div>
                    </div>
                    <span class="ms muted">chevron_right</span>
                </a>
            @empty
                <div class="empty"><span class="ms">verified</span>Başarısız üretim yok</div>
            @endforelse
        </div>
    </div>
@endsection
