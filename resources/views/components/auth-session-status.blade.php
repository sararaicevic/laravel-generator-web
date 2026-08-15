@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md border border-emerald-300/30 bg-emerald-300/10 p-3 text-sm font-medium text-emerald-200']) }}>
        {{ $status }}
    </div>
@endif
