@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md border border-[#BBF7D0] bg-[#DCFCE7] p-3 text-sm font-medium text-[#047857]']) }}>
        {{ $status }}
    </div>
@endif
