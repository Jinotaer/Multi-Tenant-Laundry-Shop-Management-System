<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Services & Pricing</h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-4">
        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200">{{ session('success') }}</div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Configure your laundry services and pricing.</p>
                <p class="mt-1 text-xs text-gray-400">
                    {{ $pricingMode === 'advanced' ? 'Advanced pricing is enabled: per-kilo, per-load, per-piece, and flat-rate services are available.' : 'Simple pricing is enabled: this shop is limited to per-kilo services.' }}
                </p>
            </div>
            <a href="{{ route('tenant.services.create') }}"
                class="inline-flex items-center gap-2 rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2 text-sm font-medium text-white shadow-sm transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Service
            </a>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            @if ($services->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                    </svg>
                    <p class="text-gray-500 text-sm font-medium">No services yet</p>
                    <p class="text-gray-400 text-xs mt-1">Add your laundry services and set pricing.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($services as $service)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $service->name }}</div>
                                            @if ($service->description)
                                                <div class="text-xs text-gray-500 mt-0.5">{{ Str::limit($service->description, 60) }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div>
                                            <p>{{ \App\Models\Service::priceTypeLabels()[$service->price_type] ?? $service->price_type }}</p>
                                            <p class="mt-0.5 text-xs text-gray-400">{{ $priceTypeDescriptions[$service->price_type] ?? '' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $service->formatted_price }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $service->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <a href="{{ route('tenant.services.edit', $service) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        <form method="POST" action="{{ route('tenant.services.destroy', $service) }}" class="inline"
                                            onsubmit="return confirm('Delete service {{ $service->name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-tenant-layout>
