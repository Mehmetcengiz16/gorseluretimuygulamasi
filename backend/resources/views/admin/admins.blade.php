@extends('admin.layout')

@section('title', 'Yöneticiler')
@section('subtitle', 'Panel erişimi olan hesaplar')

@section('content')
    <div class="grid grid-sidebar">
        <div class="card flush">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Ad</th><th>E-posta</th><th>Rol</th><th>Eklenme</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($admins as $a)
                        <tr>
                            <td style="font-weight:600">{{ $a->name }}</td>
                            <td>{{ $a->email }}</td>
                            <td><span class="badge {{ $a->isSuperAdmin() ? 'badge-pro' : '' }}">{{ $a->role->label() }}</span></td>
                            <td class="body-sm muted">{{ $a->created_at->format('d.m.Y') }}</td>
                            <td class="right">
                                @unless ($a->is(auth('admin')->user()))
                                    <form method="POST" action="{{ route('admin.admins.destroy', $a) }}" data-confirm="{{ $a->name }} silinsin mi?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger btn-icon"><span class="ms">delete</span></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.admins.store') }}" class="card stack">
            @csrf
            <div class="card-title"><span class="ms">person_add</span>Yönetici Ekle</div>
            <div class="field"><label>Ad</label><input class="input" name="name" value="{{ old('name') }}" required></div>
            <div class="field"><label>E-posta</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
            <div class="field"><label>Şifre</label><input class="input" type="password" name="password" required minlength="8"></div>
            <div class="field">
                <label>Rol</label>
                <select class="select" name="role">
                    @foreach ($roles as $role)<option value="{{ $role->value }}">{{ $role->label() }}</option>@endforeach
                </select>
                <div class="help">Editör; kredi, ayar ve yönetici işlemlerini yapamaz.</div>
            </div>
            <button class="btn btn-primary"><span class="ms">add</span>Ekle</button>
        </form>
    </div>
@endsection
