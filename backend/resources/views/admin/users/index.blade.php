@extends('admin.layout')

@section('title', 'Kullanıcılar')
@section('subtitle', 'Hesaplar, krediler ve PRO üyelikler')

@section('content')
    <div class="row between wrap">
        <div class="chips">
            <a href="{{ route('admin.users.index', request()->except('filter', 'page')) }}" class="chip {{ ! request('filter') ? 'active' : '' }}"><span class="ms">stars</span>Tümü</a>
            <a href="{{ route('admin.users.index', ['filter' => 'pro'] + request()->except('page')) }}" class="chip {{ request('filter') === 'pro' ? 'active' : '' }}"><span class="ms">workspace_premium</span>PRO</a>
            <a href="{{ route('admin.users.index', ['filter' => 'disabled'] + request()->except('page')) }}" class="chip {{ request('filter') === 'disabled' ? 'active' : '' }}"><span class="ms">block</span>Engelli</a>
        </div>
        <form class="search" method="GET">
            <span class="ms">search</span>
            <input name="q" value="{{ request('q') }}" placeholder="Ad veya e-posta ara...">
            @if (request('filter'))<input type="hidden" name="filter" value="{{ request('filter') }}">@endif
        </form>
    </div>

    <div class="card flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Kredi</th>
                    <th>Üyelik</th>
                    <th>Üretim</th>
                    <th>Durum</th>
                    <th>Kayıt</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="row">
                                <span class="avatar">
                                    @if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="">@else{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}@endif
                                </span>
                                <div style="min-width:0">
                                    <div style="font-weight:600">{{ $user->name }}</div>
                                    <div class="body-sm muted">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-amber"><span class="ms">toll</span>{{ $user->credit_balance }}</span></td>
                        <td>
                            @if ($user->isPro())
                                <span class="badge badge-pro"><span class="ms">workspace_premium</span>PRO · {{ $user->pro_expires_at->format('d.m.Y') }}</span>
                            @else
                                <span class="muted">Ücretsiz</span>
                            @endif
                        </td>
                        <td>{{ $user->generations_count }}</td>
                        <td>
                            @if ($user->is_active)<span class="badge badge-success">Aktif</span>@else<span class="badge badge-error">Engelli</span>@endif
                        </td>
                        <td class="body-sm muted nowrap">{{ $user->created_at->format('d.m.Y') }}</td>
                        <td class="right"><a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm">Detay <span class="ms">chevron_right</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty"><span class="ms">person_search</span>Kullanıcı bulunamadı</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
@endsection
