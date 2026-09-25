@extends('admin.layout')

@section('title', $config['title'])
@section('subtitle', 'Mobil uygulamada anında görünür')

@section('actions')
    <a href="{{ route('admin.catalog.create', $resource) }}" class="btn btn-primary"><span class="ms">add</span>Yeni {{ $config['singular'] }}</a>
@endsection

@section('content')
    <div class="row between">
        <form class="search" method="GET">
            <span class="ms">search</span>
            <input name="q" value="{{ request('q') }}" placeholder="{{ $config['singular'] }} ara...">
        </form>
    </div>

    <div class="card flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    @foreach ($config['columns'] as $label)<th>{{ $label }}</th>@endforeach
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        @foreach ($config['columns'] as $column => $label)
                            @php $value = data_get($item, $column); @endphp
                            <td>
                                @if (in_array($column, ['thumbnail_path', 'cover_path']))
                                    @if ($value)
                                        <img class="{{ $column === 'cover_path' ? 'thumb-lg' : 'thumb' }}" src="{{ \App\Support\Media::url($value) }}" alt="" loading="lazy">
                                    @else
                                        <span class="thumb" style="display:grid;place-items:center"><span class="ms muted">block</span></span>
                                    @endif
                                @elseif ($column === 'icon')
                                    <span class="ms amber">{{ $value ?: 'help' }}</span>
                                @elseif (in_array($column, ['is_active', 'is_pro']))
                                    @if ($value)
                                        <span class="badge {{ $column === 'is_pro' ? 'badge-pro' : 'badge-success' }}">{{ $column === 'is_pro' ? 'PRO' : 'Aktif' }}</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                @elseif ($column === 'badge')
                                    @if ($value)<span class="badge {{ $value === 'trend' ? 'badge-trend' : 'badge-pro' }}">{{ \App\Models\Template::BADGES[$value] ?? $value }}</span>@else<span class="muted">—</span>@endif
                                @elseif (in_array($column, ['name', 'title']))
                                    <span style="font-weight:600">{{ $value }}</span>
                                @elseif (in_array($column, ['likes_count', 'uses_count']))
                                    <span class="row gap-sm"><span class="ms fill amber" style="font-size:14px">{{ $column === 'likes_count' ? 'thumb_up' : 'bolt' }}</span>{{ number_format($value, 0, ',', '.') }}</span>
                                @else
                                    <span class="{{ $column === 'model_id' || $column === 'slug' || $column === 'key' ? 'mono muted' : '' }}">{{ $value ?? '—' }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="right nowrap">
                            <div class="row" style="justify-content:flex-end;gap:6px">
                                @if ($resource === 'templates')
                                    <a href="{{ route('admin.catalog.preview', [$resource, $item->id]) }}" class="btn btn-sm btn-ghost" title="Prompt önizle"><span class="ms">visibility</span></a>
                                @endif
                                <a href="{{ route('admin.catalog.edit', [$resource, $item->id]) }}" class="btn btn-sm"><span class="ms">edit</span>Düzenle</a>
                                <form method="POST" action="{{ route('admin.catalog.destroy', [$resource, $item->id]) }}" data-confirm="Bu kayıt silinsin mi?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger btn-icon" title="Sil"><span class="ms">delete</span></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($config['columns']) + 1 }}"><div class="empty"><span class="ms">{{ $config['icon'] }}</span>Kayıt yok</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
@endsection
