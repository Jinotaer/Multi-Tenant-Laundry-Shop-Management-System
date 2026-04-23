@props([
    'title',
    'description' => null,
])

<div class="flex items-end justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-slate-100 tracking-tight">{{ $title }}</h2>
        @if($description)
            <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">{{ $description }}</p>
        @endif
    </div>

    <div class="flex items-center gap-3">
        @if(isset($actions) && (string) $actions !== '')
            {{ $actions }}
        @else
            <span class="hidden sm:block text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">
                {{ now()->format('D, M j') }}
            </span>
        @endif
    </div>
</div>
