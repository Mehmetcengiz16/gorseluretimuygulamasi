@php
    $value = $status instanceof \BackedEnum ? $status->value : $status;
    $label = method_exists($status, 'label') ? $status->label() : $value;
    $class = match ($value) {
        'completed', 'done' => 'badge-success',
        'failed' => 'badge-error',
        'partial' => 'badge-amber',
        'processing', 'queued', 'pending' => 'badge-info',
        default => '',
    };
@endphp
<span class="badge {{ $class }}">
    @if (in_array($value, ['processing', 'queued', 'pending']))<span class="dot pulse"></span>@endif
    {{ $label }}
</span>
