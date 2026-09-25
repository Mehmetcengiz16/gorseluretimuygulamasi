@extends('admin.layout')

@section('title', 'Başarısız İşler')
@section('subtitle', 'Kuyrukta tüm denemeleri tükenen işler')

@section('content')
    <div class="card flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Zaman</th><th>İş</th><th>Kuyruk</th><th>Hata</th><th></th></tr></thead>
                <tbody>
                @forelse ($jobs as $job)
                    @php $payload = json_decode($job->payload, true); @endphp
                    <tr>
                        <td class="body-sm muted nowrap">{{ \Illuminate\Support\Carbon::parse($job->failed_at)->format('d.m.Y H:i') }}</td>
                        <td class="mono">{{ class_basename($payload['displayName'] ?? '?') }}</td>
                        <td><span class="badge">{{ $job->queue }}</span></td>
                        <td><div class="body-sm error-text truncate" style="max-width:420px" title="{{ \Illuminate\Support\Str::limit($job->exception, 1000) }}">{{ \Illuminate\Support\Str::before($job->exception, "\n") }}</div></td>
                        <td class="right nowrap">
                            @if (auth('admin')->user()->isSuperAdmin())
                                <div class="row" style="justify-content:flex-end;gap:6px">
                                    <form method="POST" action="{{ route('admin.logs.failed.retry', $job->uuid) }}">@csrf<button class="btn btn-sm"><span class="ms">refresh</span>Tekrar</button></form>
                                    <form method="POST" action="{{ route('admin.logs.failed.forget', $job->uuid) }}" data-confirm="Kayıt silinsin mi?">@csrf @method('DELETE')<button class="btn btn-sm btn-danger btn-icon"><span class="ms">delete</span></button></form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty"><span class="ms">verified</span>Başarısız iş yok</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $jobs->links() }}
    </div>
@endsection
