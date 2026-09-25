@extends('admin.layout')

@section('title', 'Kredi Hareketleri')
@section('subtitle', 'Salt okunur kredi defteri')

@section('content')
    <div class="chips">
        <a href="{{ route('admin.logs.credits') }}" class="chip {{ ! request('type') ? 'active' : '' }}">Tümü</a>
        @foreach ($types as $type)
            <a href="{{ route('admin.logs.credits', ['type' => $type->value]) }}" class="chip {{ request('type') === $type->value ? 'active' : '' }}">{{ $type->label() }}</a>
        @endforeach
    </div>

    <div class="card flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Tarih</th><th>Kullanıcı</th><th>Tür</th><th>Açıklama</th><th class="right">Miktar</th><th class="right">Bakiye</th></tr></thead>
                <tbody>
                @forelse ($transactions as $t)
                    <tr>
                        <td class="body-sm muted nowrap">{{ $t->created_at->format('d.m.Y H:i') }}</td>
                        <td>@if ($t->user)<a href="{{ route('admin.users.show', $t->user) }}">{{ $t->user->name }}</a>@else — @endif</td>
                        <td><span class="badge">{{ $t->type->label() }}</span></td>
                        <td>{{ $t->description }} @if ($t->admin)<span class="body-sm muted">· {{ $t->admin->name }}</span>@endif</td>
                        <td class="right" style="font-weight:700;color:{{ $t->amount >= 0 ? 'var(--success)' : 'var(--error)' }}">{{ $t->amount > 0 ? '+' : '' }}{{ $t->amount }}</td>
                        <td class="right">{{ $t->balance_after }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><span class="ms">toll</span>Hareket yok</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $transactions->links() }}
    </div>
@endsection
