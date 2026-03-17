<section class="tenant-panel overflow-hidden" data-widget-key="enabled_features">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-800">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Access</p>
        <h3 class="mt-1 text-lg font-semibold text-gray-900">Enabled Features</h3>
    </div>
    <div class="flex flex-wrap gap-2 p-6">
        @foreach (config('themes.features', []) as $featureKey => $featureConfig)
            @feature($featureKey)
                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                    <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    {{ $featureConfig['label'] }}
                </span>
            @else
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-400 dark:bg-slate-800 dark:text-slate-500">
                    <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    {{ $featureConfig['label'] }}
                </span>
            @endfeature
        @endforeach
    </div>
</section>
