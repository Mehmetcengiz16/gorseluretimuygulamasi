@extends('admin.layout')

@section('title', 'Ayarlar')
@section('subtitle', 'Yapay zekâ, kredi ve uygulama ayarları')

@section('actions')
    @if (auth('admin')->user()->isSuperAdmin())
        <form method="POST" action="{{ route('admin.settings.test') }}">
            @csrf
            <button class="btn"><span class="ms">network_check</span>Bağlantıyı Test Et</button>
        </form>
    @endif
@endsection

@section('content')
    <div class="card row between">
        <div class="row">
            <span class="dot {{ $aiMode === 'live' ? '' : 'pulse' }}" style="{{ $aiMode === 'live' ? 'background:var(--success);box-shadow:0 0 8px var(--success)' : '' }}"></span>
            <div>
                <div class="title-md">{{ $aiMode === 'live' ? 'OpenRouter canlı modda' : 'Sahte (test) modda' }}</div>
                <div class="body-sm muted">
                    @if ($aiMode === 'live')
                        Görseller <span class="mono">{{ $values['ai.image_model'] }}</span> ile üretiliyor ve maliyet oluşuyor.
                    @elseif (! $maskedKey)
                        API anahtarı girilmedi. Aşağıdaki "OpenRouter API anahtarı" alanına anahtarını yazıp kaydet.
                    @else
                        Anahtar kayıtlı ama "Sahte mod" açık. Kapatıp kaydettiğinde canlıya geçer.
                    @endif
                </div>
            </div>
        </div>
        <div class="row gap-sm">
            @if ($maskedKey)
                <span class="badge badge-success"><span class="ms">key</span><span class="mono" style="text-transform:none">{{ $maskedKey }}</span></span>
                <span class="badge">{{ $keySource === 'panel' ? 'Panelden' : '.env' }}</span>
            @else
                <span class="badge badge-error"><span class="ms">key_off</span>Anahtar yok</span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="stack">
        @csrf
        <div class="tabs" data-tabs>
            @foreach ($tabs as $key => $label)
                <button type="button" class="tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>

        @foreach ($tabs as $tabKey => $tabLabel)
            <div class="card tab-panel {{ $loop->first ? 'active' : '' }}" data-panel="{{ $tabKey }}">
                <div class="form-grid">
                    @foreach ($fields as $key => [$label, $type, $tab, $help])
                        @continue($tab !== $tabKey)
                        @php $input = str_replace('.', '_', $key); $value = $values[$key]; @endphp
                        @if ($type === 'secret')
                            <div class="field full">
                                <label for="{{ $input }}">{{ $label }}</label>
                                <div class="row">
                                    <input class="input mono" id="{{ $input }}" name="{{ $input }}" type="password" autocomplete="off" spellcheck="false"
                                           placeholder="{{ $maskedKey ? 'Kayıtlı: '.$maskedKey.' — değiştirmek için yeni anahtarı yapıştır' : 'sk-or-v1-...' }}">
                                    <button type="button" class="btn btn-icon" title="Göster / gizle"
                                            onclick="const i=document.getElementById('{{ $input }}'); i.type = i.type === 'password' ? 'text' : 'password'"><span class="ms">visibility</span></button>
                                </div>
                                <div class="row between wrap">
                                    <div class="help">{{ $help }} <a href="https://openrouter.ai/keys" target="_blank" rel="noopener">Anahtar oluştur</a></div>
                                    @if ($keySource === 'panel')
                                        <label class="switch body-sm"><input type="checkbox" name="{{ $input }}_clear" value="1"><span class="track"></span>Kayıtlı anahtarı sil</label>
                                    @endif
                                </div>
                            </div>
                        @elseif (in_array($type, ['image_model', 'text_model']))
                            <div class="field">
                                <label for="{{ $input }}">{{ $label }}</label>
                                <input class="input mono" id="{{ $input }}" name="{{ $input }}" value="{{ $value }}" list="list-{{ $type }}" autocomplete="off" spellcheck="false">
                                @if ($help)<div class="help">{{ $help }}</div>@endif
                            </div>
                        @elseif ($type === 'checkbox')
                            <div class="field full">
                                <label class="switch">
                                    <input type="checkbox" name="{{ $input }}" value="1" @checked($value)>
                                    <span class="track"></span>{{ $label }}
                                </label>
                                @if ($help)<div class="help">{{ $help }}</div>@endif
                            </div>
                        @elseif ($type === 'textarea')
                            <div class="field full">
                                <label for="{{ $input }}">{{ $label }}</label>
                                <textarea class="textarea mono" id="{{ $input }}" name="{{ $input }}" rows="8">{{ $value }}</textarea>
                                @if ($help)<div class="help">{{ $help }}</div>@endif
                            </div>
                        @else
                            <div class="field">
                                <label for="{{ $input }}">{{ $label }}</label>
                                <input class="input {{ str_contains($key, 'model') && $key !== 'ai.model_label' ? 'mono' : '' }}" id="{{ $input }}" name="{{ $input }}" value="{{ $value }}" type="{{ $type === 'number' ? 'number' : 'text' }}" step="any">
                                @if ($help)<div class="help">{{ $help }}</div>@endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        @foreach ($suggestions as $type => $models)
            <datalist id="list-{{ $type }}">
                @foreach ($models as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
            </datalist>
        @endforeach

        @if (auth('admin')->user()->isSuperAdmin())
            <div class="row" style="justify-content:flex-end">
                <button class="btn btn-primary"><span class="ms">save</span>Ayarları Kaydet</button>
            </div>
        @endif
    </form>
@endsection
