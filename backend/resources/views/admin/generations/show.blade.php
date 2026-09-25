@extends('admin.layout')

@section('title', 'Üretim #'.$generation->id)
@section('subtitle', ($generation->project?->title ?: 'İsimsiz ürün').' · '.$generation->created_at->format('d.m.Y H:i'))

@section('actions')
    @if ($generation->images->contains(fn ($i) => $i->status->value === 'failed'))
        <form method="POST" action="{{ route('admin.generations.retry', $generation) }}" data-confirm="Başarısız varyantlar tekrar kuyruğa alınsın mı?">
            @csrf
            <button class="btn"><span class="ms">refresh</span>Yeniden Dene</button>
        </form>
    @endif
    @if ($generation->credits_charged > $generation->credits_refunded)
        <form method="POST" action="{{ route('admin.generations.refund', $generation) }}" data-confirm="Kalan {{ $generation->credits_charged - $generation->credits_refunded }} kredi kullanıcıya iade edilsin mi?">
            @csrf
            <button class="btn btn-danger"><span class="ms">undo</span>Kredi İade Et</button>
        </form>
    @endif
@endsection

@section('content')
    <div class="grid grid-sidebar">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <div class="card-title"><span class="ms">compare</span>Orijinal & Dekupe</div>
                    @if ($generation->project)@include('admin.partials.status', ['status' => $generation->project->cutout_status])@endif
                </div>
                <div class="compare">
                    <figure>
                        <img src="{{ $generation->project?->originalUrl() }}" alt="Orijinal">
                        <figcaption><span class="badge">Orijinal</span></figcaption>
                    </figure>
                    <figure>
                        @if ($generation->project?->cutoutUrl())
                            <img src="{{ $generation->project->cutoutUrl() }}" alt="Dekupe">
                        @else
                            <div class="placeholder" style="aspect-ratio:4/5"><span class="ms">content_cut</span></div>
                        @endif
                        <figcaption><span class="badge badge-amber">AI Dekupe</span></figcaption>
                    </figure>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-title"><span class="ms">auto_awesome</span>Stüdyo Varyasyonları</div>
                    <span class="label-md primary">{{ $generation->images->where('status.value', 'done')->count() }} / {{ $generation->variant_count }} Hazır</span>
                </div>
                <div class="variant-grid">
                    @foreach ($generation->images as $image)
                        <div class="variant {{ $image->is_master ? 'master' : '' }}">
                            <div class="media">
                                @if ($image->path)
                                    <a href="{{ $image->url() }}" target="_blank"><img src="{{ $image->thumbUrl() }}" alt="" loading="lazy"></a>
                                @else
                                    <div class="placeholder">
                                        <div style="text-align:center">
                                            <span class="ms">{{ $image->status->value === 'failed' ? 'broken_image' : 'hourglass_top' }}</span>
                                            <div class="body-sm" style="margin-top:6px">@include('admin.partials.status', ['status' => $image->status])</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @if ($image->is_favorite)<span class="fav"><span class="ms fill" style="font-size:18px">star</span></span>@endif
                            <span class="tag {{ $image->is_master ? 'master' : '' }}">V{{ $image->variant_index }}{{ $image->is_master ? ' MASTER' : '' }}</span>
                            @if ($image->edit_tool)
                                <span class="badge badge-info" style="position:absolute;top:8px;left:8px"><span class="ms">auto_fix_high</span>{{ $image->editLabel() }} · V{{ $image->source?->variant_index }}</span>
                            @endif
                            @if ($image->error_message)
                                <div class="body-sm error-text" style="padding:8px 10px">{{ \Illuminate\Support\Str::limit($image->error_message, 140) }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-title"><span class="ms">psychology</span>Modele Giden Prompt</div>
                    <button class="btn btn-sm" type="button" data-copy="final-prompt"><span class="ms">content_copy</span><span>Kopyala</span></button>
                </div>
                <div class="prompt-box" id="final-prompt">{{ $generation->final_prompt }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div class="card-title"><span class="ms">info</span>Detaylar</div>
                @include('admin.partials.status', ['status' => $generation->status])
            </div>
            <dl class="dl">
                <dt>Kullanıcı</dt><dd><a href="{{ route('admin.users.show', $generation->user) }}">{{ $generation->user->name }}</a></dd>
                <dt>Kullanıcı prompt'u</dt><dd>{{ $generation->user_prompt ?: '—' }}</dd>
                <dt>Şablon</dt><dd>{{ $generation->template?->title ?? '—' }}</dd>
                <dt>Sahne</dt><dd>{{ $generation->sceneType?->name ?? '—' }}</dd>
                <dt>Stil</dt><dd>{{ $generation->studioStyle?->name ?? '—' }}</dd>
                <dt>Işık</dt><dd>{{ $generation->lightingPreset?->name ?? '—' }}</dd>
                <dt>Kalite</dt><dd>{{ $generation->qualityLevel?->name ?? '—' }}</dd>
                <dt>Gölge & Yansıma</dt><dd>{{ $generation->shadow_enabled ? 'Açık' : 'Kapalı' }}</dd>
                <dt>Oran</dt><dd>{{ $generation->aspect_ratio }}</dd>
                <dt>Model</dt><dd class="mono">{{ $generation->model_id }}</dd>
                <dt>Kredi</dt><dd>{{ $generation->credits_charged }} harcandı · {{ $generation->credits_refunded }} iade</dd>
                <dt>Maliyet</dt><dd>${{ number_format($generation->cost_usd, 4) }}</dd>
                <dt>Başladı</dt><dd>{{ $generation->started_at?->format('d.m.Y H:i:s') ?? '—' }}</dd>
                <dt>Bitti</dt><dd>{{ $generation->completed_at?->format('d.m.Y H:i:s') ?? '—' }}</dd>
                @if ($generation->error_message)
                    <dt>Hata</dt><dd class="error-text">{{ $generation->error_message }}</dd>
                @endif
            </dl>
        </div>
    </div>
@endsection
