@extends('admin.layout')

@section('title', 'Üretimler')
@section('subtitle', 'Tüm stüdyo çekimleri')

@section('content')
    <div class="row between wrap">
        <div class="chips">
            <a href="{{ route('admin.generations.index', request()->except('status', 'page')) }}" class="chip {{ ! request('status') ? 'active' : '' }}"><span class="ms">stars</span>Tümü</a>
            @foreach ($statuses as $s)
                <a href="{{ route('admin.generations.index', ['status' => $s->value] + request()->except('page')) }}" class="chip {{ request('status') === $s->value ? 'active' : '' }}">{{ $s->label() }}</a>
            @endforeach
        </div>
        @if (request('user'))
            <a href="{{ route('admin.generations.index', request()->except('user', 'page')) }}" class="btn btn-sm"><span class="ms">close</span>Kullanıcı filtresi #{{ request('user') }}</a>
        @endif
    </div>

    <div class="card flush">
        @if ($generations->isEmpty())
            <div class="empty"><span class="ms">image</span>Üretim bulunamadı</div>
        @else
            <div class="gallery" style="padding:20px">
                @foreach ($generations as $g)
                    @include('admin.generations.card', ['g' => $g])
                @endforeach
            </div>
        @endif
        {{ $generations->links() }}
    </div>
@endsection
