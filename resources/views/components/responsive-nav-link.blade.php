@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-md border border-emerald-300/25 bg-emerald-300/10 px-4 py-3 text-start text-base font-semibold text-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-300/70 transition duration-150 ease-in-out'
            : 'block w-full rounded-md border border-transparent px-4 py-3 text-start text-base font-semibold text-zinc-400 hover:border-white/10 hover:bg-white/[0.06] hover:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
