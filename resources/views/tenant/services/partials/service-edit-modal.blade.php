<x-modal name="service-edit-modal" :show="$showServiceEditModal" maxWidth="2xl" focusable>
    <form method="POST" action="{{ $editService ? route('tenant.services.update', $editService) : '#' }}" class="p-6" id="service-edit-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_context" value="service-edit">
        <input type="hidden" name="service_id" value="{{ old('form_context') === 'service-edit' ? old('service_id', $editService?->getKey()) : $editService?->getKey() }}">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Services</p>
                <h3 class="mt-2 text-lg font-semibold text-gray-900">Edit Service</h3>
                <p class="mt-1 text-sm text-gray-500">Update the service details below.</p>
            </div>

            <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-5">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                @if ($pricingMode === 'advanced')
                    <p class="font-medium text-gray-800">Advanced pricing is enabled for this shop.</p>
                    <p class="mt-1">You can keep using per-kilo, per-load, per-piece, and flat-rate services.</p>
                @else
                    <p class="font-medium text-gray-800">Simple pricing is enabled for this shop.</p>
                    <p class="mt-1">This shop is restricted to per-kilo services.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Service Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('form_context') === 'service-edit' ? old('name') : ($editService->name ?? '') }}" required
                    class="block w-full rounded-md {{ $errors->has('name') && old('form_context') === 'service-edit' ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @if ($errors->has('name') && old('form_context') === 'service-edit')
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('name') }}</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2"
                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('form_context') === 'service-edit' ? old('description') : ($editService->description ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price Type <span class="text-red-500">*</span></label>
                    <select name="price_type" required
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($priceTypes as $key => $label)
                            @php
                                $selectedType = old('form_context') === 'service-edit' ? old('price_type') : ($editService->price_type ?? '');
                            @endphp
                            <option value="{{ $key }}" {{ $selectedType === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (₱) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('form_context') === 'service-edit' ? old('price') : ($editService->price ?? '') }}" min="0" step="0.01" required
                        class="block w-full rounded-md {{ $errors->has('price') && old('form_context') === 'service-edit' ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @if ($errors->has('price') && old('form_context') === 'service-edit')
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('price') }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('form_context') === 'service-edit' ? old('sort_order') : ($editService->sort_order ?? 0) }}" min="0"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        @php
                            $isActive = old('form_context') === 'service-edit' ? old('is_active') : ($editService->is_active ?? true);
                        @endphp
                        <input type="checkbox" name="is_active" value="1" {{ $isActive ? 'checked' : '' }}
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                <p class="text-sm font-medium text-gray-800">Available pricing types</p>
                <div class="mt-3 space-y-2 text-sm text-gray-600">
                    @foreach ($priceTypes as $key => $label)
                        <div>
                            <p class="font-medium text-gray-700">{{ $label }}</p>
                            <p>{{ $priceTypeDescriptions[$key] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                Update Service
            </button>
        </div>
    </form>
</x-modal>
