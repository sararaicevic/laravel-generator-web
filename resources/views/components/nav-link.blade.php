@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-md border border-emerald-300/25 bg-emerald-300/10 px-3 py-2 text-sm font-semibold leading-5 text-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-300/70 transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-semibold leading-5 text-zinc-400 hover:border-white/10 hover:bg-white/[0.06] hover:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
