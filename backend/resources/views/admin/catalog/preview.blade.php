@extends('admin.layout')

@section('title', 'Prompt Önizleme')
@section('subtitle', $template->title)

@section('actions')
    <a href="{{ route('admin.catalog.edit', ['templates', $template->id]) }}" class="btn"><span class="ms">edit</span>Şablonu Düzenle</a>
@endsection

@section('content')
    <div class="grid grid-sidebar">
        <div class="stack">
            <div class="card">
                <div class="card-head"><div class="card-title"><span class="ms">shield</span>Sistem Mesajı</div></div>
                <div class="prompt-box">{{ $system }}</div>
            </div>
            <div class="card">
                <div class="card-head">
                    <div class="card-title"><span class="ms">psychology</span>Kullanıcı Mesajı (V1)</div>
                    <button class="btn btn-sm" type="button" data-copy="user-prompt"><span class="ms">content_copy</span><span>Kopyala</span></button>
                </div>
                <div class="prompt-box" id="user-prompt">{{ $prompt }}</div>
                <div class="help" style="margin-top:10px">Gölge açık ve 4:5 oran varsayılarak üretildi. Her varyanta farklı bir kamera ipucu eklenir.</div>
            </div>
        </div>
        <div class="gen-card">
            <div class="media"><img src="{{ $template->coverUrl() }}" alt=""></div>
            <div class="body">
                <div class="title-md">{{ $template->title }}</div>
                <div class="body-sm muted">{{ $template->subtitle }}</div>
            </div>
        </div>
    </div>
@endsection
