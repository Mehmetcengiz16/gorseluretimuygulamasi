@extends('admin.layout')

@section('title', $user->name)
@section('subtitle', $user->email)

@section('actions')
    <a href="{{ route('admin.generations.index', ['user' => $user->id]) }}" class="btn"><span class="ms">auto_awesome</span>Üretimleri</a>
    <form method="POST" action="{{ route('admin.users.toggle', $user) }}" data-confirm="{{ $user->is_active ? 'Hesap engellensin ve tüm oturumları kapatılsın mı?' : 'Hesap aktifleştirilsin mi?' }}">
        @csrf
        <button class="btn {{ $user->is_active ? 'btn-danger' : '' }}">
            <span class="ms">{{ $user->is_active ? 'block' : 'check_circle' }}</span>{{ $user->is_active ? 'Engelle' : 'Aktifleştir' }}
        </button>
    </form>
@endsection

@section('content')
    <div class="grid grid-sidebar">
        <div class="stack">
            <div class="grid grid-3">
                <div class="stat">
                    <div class="stat-label"><span class="ms">toll</span>Kredi Bakiyesi</div>
                    <div class="stat-value amber">{{ $user->credit_balance }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label"><span class="ms">workspace_premium</span>Üyelik</div>
                    <div class="stat-value" style="font-size:22px">{{ $user->isPro() ? 'PRO' : 'Ücretsiz' }}</div>
                    <div class="stat-sub">{{ $user->isPro() ? $user->pro_expires_at->format('d.m.Y').' tarihine kadar' : '' }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label"><span class="ms">schedule</span>Son Giriş</div>
                    <div class="stat-value" style="font-size:18px">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</div>
                    <div class="stat-sub">Kayıt: {{ $user->created_at->format('d.m.Y') }}</div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><div class="card-title"><span class="ms">photo_library</span>Son Projeler</div></div>
                @if ($projects->isEmpty())
                    <div class="empty"><span class="ms">image</span>Henüz proje yok</div>
                @else
                    <div class="gallery">
                        @foreach ($projects as $project)
                            <div class="gen-card">
                                <div class="media"><img src="{{ $project->originalUrl() }}" alt="" loading="lazy"></div>
                                <div class="body">
                                    <div class="title-md truncate">{{ $project->title ?: 'Proje #'.$project->id }}</div>
                                    <div class="body-sm muted">{{ $project->generations_count }} üretim · {{ $project->created_at->format('d.m.Y') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card flush">
                <div class="card-head"><div class="card-title"><span class="ms">receipt_long</span>Kredi Geçmişi</div></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Tarih</th><th>Tür</th><th>Açıklama</th><th class="right">Miktar</th><th class="right">Bakiye</th></tr></thead>
                        <tbody>
                        @forelse ($transactions as $t)
                            <tr>
                                <td class="body-sm muted nowrap">{{ $t->created_at->format('d.m.Y H:i') }}</td>
                                <td><span class="badge">{{ $t->type->label() }}</span></td>
                                <td>{{ $t->description }} @if ($t->admin)<span class="body-sm muted">· {{ $t->admin->name }}</span>@endif</td>
                                <td class="right" style="font-weight:700;color:{{ $t->amount >= 0 ? 'var(--success)' : 'var(--error)' }}">{{ $t->amount > 0 ? '+' : '' }}{{ $t->amount }}</td>
                                <td class="right">{{ $t->balance_after }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty">Hareket yok</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $transactions->links() }}
            </div>
        </div>

        <div class="stack">
            <form method="POST" action="{{ route('admin.users.credits', $user) }}" class="card stack">
                @csrf
                <div class="card-title"><span class="ms">add_card</span>Kredi Ekle / Düş</div>
                <div class="field">
                    <label>Miktar</label>
                    <input class="input" type="number" name="amount" placeholder="Ör. 20 veya -5" required>
                    <div class="help">Negatif değer krediyi düşer.</div>
                </div>
                <div class="field">
                    <label>Açıklama</label>
                    <input class="input" name="description" placeholder="Ör. Destek talebi telafisi" required>
                </div>
                <button class="btn btn-primary"><span class="ms">check</span>Uygula</button>
            </form>

            <form method="POST" action="{{ route('admin.users.pro', $user) }}" class="card stack">
                @csrf
                <div class="card-title"><span class="ms">workspace_premium</span>PRO Üyelik</div>
                <div class="field">
                    <label>Bitiş tarihi</label>
                    <input class="input" type="date" name="pro_expires_at" value="{{ $user->pro_expires_at?->format('Y-m-d') }}">
                    <div class="help">Boş bırakırsan PRO kaldırılır.</div>
                </div>
                <button class="btn"><span class="ms">save</span>Kaydet</button>
            </form>
        </div>
    </div>
@endsection
