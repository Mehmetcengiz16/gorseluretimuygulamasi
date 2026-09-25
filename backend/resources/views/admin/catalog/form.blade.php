@extends('admin.layout')

@section('title', ($item->exists ? 'Düzenle: ' : 'Yeni ').$config['singular'])
@section('subtitle', $config['title'])

@section('actions')
    <a href="{{ route('admin.catalog.index', $resource) }}" class="btn btn-ghost"><span class="ms">arrow_back</span>Geri</a>
@endsection

@section('content')
    <form method="POST" enctype="multipart/form-data" class="card"
          action="{{ $item->exists ? route('admin.catalog.update', [$resource, $item->id]) : route('admin.catalog.store', $resource) }}">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <div class="form-grid">
            @foreach ($fields as $name => $field)
                @php
                    [$label, $type] = $field;
                    $help = $field[3] ?? null;
                    $options = $field[4] ?? [];
                    $value = old($name, $item->{$name});
                    if ($type === 'checkbox' && ! $item->exists && $name === 'is_active') $value = true;
                @endphp

                @if ($type === 'checkbox')
                    <div class="field" style="justify-content:flex-end">
                        <label class="switch">
                            <input type="checkbox" name="{{ $name }}" value="1" @checked($value)>
                            <span class="track"></span>{{ $label }}
                        </label>
                    </div>
                @elseif ($type === 'textarea')
                    <div class="field full">
                        <label for="{{ $name }}">{{ $label }}</label>
                        <textarea class="textarea" id="{{ $name }}" name="{{ $name }}" rows="4">{{ $value }}</textarea>
                        @if ($help)<div class="help">{{ $help }}</div>@endif
                    </div>
                @elseif ($type === 'image')
                    <div class="field full">
                        <label>{{ $label }}</label>
                        <div class="row" style="align-items:flex-start;gap:16px">
                            <img id="preview-{{ $name }}" src="{{ \App\Support\Media::url($item->{$name}) }}" alt=""
                                 class="{{ $item->{$name} ? '' : 'hidden' }}"
                                 style="width:120px;aspect-ratio:{{ $name === 'cover_path' ? '4/5' : '1/1' }};object-fit:cover;border-radius:12px;background:var(--surface-high)">
                            <div class="stack" style="gap:10px;flex:1">
                                <input class="input-file" type="file" name="{{ $name }}" accept="image/*" data-preview="preview-{{ $name }}">
                                <input class="input" name="{{ $name }}_url" placeholder="veya görsel URL'si yapıştır (https://...)">
                            </div>
                        </div>
                    </div>
                @elseif ($type === 'select')
                    <div class="field">
                        <label for="{{ $name }}">{{ $label }}</label>
                        <select class="select" id="{{ $name }}" name="{{ $name }}">
                            @unless (array_key_exists('', $options))<option value="">—</option>@endunless
                            @foreach ($options as $optValue => $optLabel)
                                <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                        @if ($help)<div class="help">{{ $help }}</div>@endif
                    </div>
                @elseif ($type === 'icon')
                    <div class="field">
                        <label for="{{ $name }}">{{ $label }}</label>
                        <div class="row">
                            <span class="btn btn-icon" style="pointer-events:none"><span class="ms amber" id="icon-{{ $name }}">{{ $value ?: 'help' }}</span></span>
                            <input class="input" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}" data-icon-preview="icon-{{ $name }}">
                        </div>
                        @if ($help)<div class="help">{{ $help }} — <a href="https://fonts.google.com/icons" target="_blank" rel="noopener">ikon listesi</a></div>@endif
                    </div>
                @else
                    <div class="field">
                        <label for="{{ $name }}">{{ $label }}</label>
                        <input class="input" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}"
                               type="{{ $type === 'number' ? 'number' : 'text' }}" @if ($type === 'number') step="any" @endif>
                        @if ($help)<div class="help">{{ $help }}</div>@endif
                    </div>
                @endif
            @endforeach
        </div>

        <div class="divider" style="margin:24px 0 20px"></div>
        <div class="row" style="justify-content:flex-end">
            <a href="{{ route('admin.catalog.index', $resource) }}" class="btn btn-ghost">Vazgeç</a>
            <button class="btn btn-primary" type="submit"><span class="ms">save</span>Kaydet</button>
        </div>
    </form>
@endsection
