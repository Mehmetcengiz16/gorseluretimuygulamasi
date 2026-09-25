<a href="{{ route('admin.generations.show', $g) }}" class="gen-card" style="color:inherit">
    <div class="media">
        @if ($g->masterImage?->path)
            <img src="{{ $g->masterImage->thumbUrl() }}" alt="" loading="lazy">
        @elseif ($g->project?->originalUrl())
            <img src="{{ $g->project->originalUrl() }}" alt="" loading="lazy" style="opacity:.35;filter:grayscale(1)">
        @else
            <div class="placeholder"><span class="ms">image</span></div>
        @endif
        <div class="top">@include('admin.partials.status', ['status' => $g->status])</div>
        <div class="overlay">
            <span class="label-md">{{ $g->variant_count }} varyant</span>
            <span class="badge badge-amber">{{ $g->credits_charged }} kr</span>
        </div>
    </div>
    <div class="body">
        <div class="title-md truncate">#{{ $g->id }} · {{ $g->project?->title ?: 'İsimsiz ürün' }}</div>
        <div class="body-sm muted truncate">{{ $g->user?->name }} · {{ $g->created_at->diffForHumans() }}</div>
    </div>
</a>
