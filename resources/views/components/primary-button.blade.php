@props(['href' => null])

@php
    $classes = 'tenant-primary-action inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none transition ease-in-out duration-150 disabled:cursor-not-allowed disabled:opacity-60';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->except(['href', 'type'])->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->except('href')->merge(['type' => 'submit'])->class($classes) }}>
        {{ $slot }}
    </button>
@endif
