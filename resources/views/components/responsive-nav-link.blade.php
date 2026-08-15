@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-md border border-[#E0E7FF] bg-[#EEF2FF] px-4 py-3 text-start text-base font-semibold text-[#6366F1] focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition duration-150 ease-in-out'
            : 'block w-full rounded-md border border-transparent px-4 py-3 text-start text-base font-semibold text-[#64748B] hover:border-[#E2E8F0] hover:bg-[#F8FAFC] hover:text-[#1E293B] focus:outline-none focus:ring-2 focus:ring-[#6366F1] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
