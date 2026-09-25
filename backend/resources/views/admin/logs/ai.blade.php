@extends('admin.layout')

@section('title', 'AI İstek Logları')
@section('subtitle', 'OpenRouter çağrıları, süreler ve maliyetler')

@section('content')
    <div class="chips">
        <a href="{{ route('admin.logs.ai') }}" class="chip {{ ! request()->hasAny(['purpose', 'errors']) ? 'active' : '' }}">Tümü</a>
        @foreach (['generate' => 'Üretim', 'edit' => 'Düzenleme', 'cutout' => 'Dekupe', 'enhance_prompt' => 'Prompt Geliştirme'] as $key => $label)
            <a href="{{ route('admin.logs.ai', ['purpose' => $key]) }}" class="chip {{ request('purpose') === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('admin.logs.ai', ['errors' => 1]) }}" class="chip {{ request('errors') ? 'active' : '' }}"><span class="ms">error</span>Hatalar</a>
    </div>

    <div class="card flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Zaman</th><th>Amaç</th><th>Model</th><th>Kullanıcı</th><th>HTTP</th><th class="right">Süre</th><th class="right">Token</th><th class="right">Maliyet</th></tr></thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="body-sm muted nowrap">{{ $log->created_at->format('d.m H:i:s') }}</td>
                        <td><span class="badge">{{ $log->purpose }}</span></td>
                        <td class="mono">{{ $log->model_id }}</td>
                        <td>{{ $log->user?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $log->error ? 'badge-error' : 'badge-success' }}">{{ $log->status_code ?? 'ERR' }}</span>
                            @if ($log->error)<div class="body-sm error-text truncate" style="max-width:280px" title="{{ $log->error }}">{{ $log->error }}</div>@endif
                        </td>
                        <td class="right nowrap">{{ $log->duration_ms !== null ? number_format($log->duration_ms / 1000, 1).' sn' : '—' }}</td>
                        <td class="right body-sm">{{ $log->prompt_tokens ?? '—' }} / {{ $log->completion_tokens ?? '—' }}</td>
                        <td class="right">${{ number_format($log->cost_usd ?? 0, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty"><span class="ms">monitoring</span>Log yok</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </div>
@endsection
