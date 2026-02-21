<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Subscription Plans') }}
            </h2>
            <a href="{{ route('admin.subscription-plans.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('Create Plan') }}
            </a>
        </div>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getAdminTheme(); @endphp

    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    @if ($plans->isEmpty())
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No subscription plans</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first pricing plan.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.subscription-plans.create') }}" class="inline-flex items-center px-4 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('Create Plan') }}
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($plans as $plan)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg relative {{ !$plan->is_active ? 'opacity-60' : '' }}">
                    @if ($plan->is_default)
                        <div class="absolute top-3 right-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Default</span>
                        </div>
                    @endif

                    @if (!$plan->is_active)
                        <div class="absolute top-3 {{ $plan->is_default ? 'right-20' : 'right-3' }}">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                        </div>
                    @endif

                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h3>

                        <div class="mt-2">
                            @if ($plan->isFree())
                                <span class="text-3xl font-bold text-gray-900">Free</span>
                            @else
                                <span class="text-3xl font-bold text-gray-900">₱{{ number_format((float) $plan->price, 0) }}</span>
                                <span class="text-sm text-gray-500">/{{ $plan->billing_cycle }}</span>
                            @endif
                        </div>

                        @if ($plan->description)
                            <p class="mt-2 text-sm text-gray-500">{{ $plan->description }}</p>
                        @endif

                        <div class="mt-4 space-y-2 border-t border-gray-100 pt-4">
                            <div class="flex items-center text-sm">
                                <svg class="mr-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                <span class="text-gray-600">Staff: <strong>{{ $plan->staff_limit_display }}</strong></span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="mr-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                                <span class="text-gray-600">Customers: <strong>{{ $plan->customer_limit_display }}</strong></span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="mr-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                                <span class="text-gray-600">Orders/month: <strong>{{ $plan->order_limit_display }}</strong></span>
                            </div>
                        </div>

                        @if (!empty($plan->features))
                            <div class="mt-4 border-t border-gray-100 pt-4">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Features</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($plan->features as $feature)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $theme['badge_bg'] }} {{ $theme['badge_text'] }}">
                                            {{ config("themes.features.{$feature}.label", ucwords(str_replace('_', ' ', $feature))) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 text-xs text-gray-400">
                            {{ $plan->tenants()->count() }} shop(s) on this plan
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-between">
                        <a href="{{ route('admin.subscription-plans.edit', $plan) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">Edit</a>

                        @if ($plan->tenants()->count() === 0)
                            <form method="POST" action="{{ route('admin.subscription-plans.destroy', $plan) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-900" onclick="return confirm('Delete this plan? This cannot be undone.')">Delete</button>
                            </form>
                        @else
                            <span class="text-xs text-gray-400">In use — cannot delete</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-admin-layout>
