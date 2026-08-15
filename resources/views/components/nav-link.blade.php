@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-md border border-[#E0E7FF] bg-[#EEF2FF] px-3 py-2 text-sm font-semibold leading-5 text-[#6366F1] focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-semibold leading-5 text-[#64748B] hover:border-[#E2E8F0] hover:bg-[#F8FAFC] hover:text-[#1E293B] focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
