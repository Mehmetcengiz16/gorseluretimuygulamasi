@if ($paginator->hasPages())
    <nav class="pagination">
        <div class="body-sm muted">
            Toplam {{ $paginator->total() }} kayıt · Sayfa {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </div>
        <div class="pages">
            <a class="page {{ $paginator->onFirstPage() ? 'disabled' : '' }}" href="{{ $paginator->previousPageUrl() }}"><span class="ms" style="font-size:18px">chevron_left</span></a>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="page disabled">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <a class="page {{ $page == $paginator->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                    @endforeach
                @endif
            @endforeach
            <a class="page {{ $paginator->hasMorePages() ? '' : 'disabled' }}" href="{{ $paginator->nextPageUrl() }}"><span class="ms" style="font-size:18px">chevron_right</span></a>
        </div>
    </nav>
@endif
