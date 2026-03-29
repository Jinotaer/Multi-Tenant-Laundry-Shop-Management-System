@php $theme = app(\App\Services\ThemeService::class)->getAdminTheme(); @endphp

<div class="space-y-6 mb-12">
    <!-- Plan Details -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Plan Details</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <x-input-label for="name" :value="__('Plan Name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $plan->name ?? '')" required placeholder="e.g. Starter, Premium" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Slug -->
                <div>
                    <x-input-label for="slug" :value="__('Slug')" />
                    <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $plan->slug ?? '')" required placeholder="e.g. starter, premium" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500">URL-friendly identifier. Use lowercase letters, numbers, and dashes only.</p>
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Brief description of this plan...">{{ old('description', $plan->description ?? '') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Pricing</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Price -->
                <div>
                    <x-input-label for="price" :value="__('Price (₱)')" />
                    <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price', $plan->price ?? 0)" required />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500">Set to 0 for a free plan.</p>
                </div>

                <!-- Billing Cycle -->
                <div>
                    <x-input-label for="billing_cycle" :value="__('Billing Cycle')" />
                    <select id="billing_cycle" name="billing_cycle" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle ?? '') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                    <x-input-error :messages="$errors->get('billing_cycle')" class="mt-2" />
                </div>
            </div>
        </div>
    </div>

    <!-- Limits -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Usage Limits</h3>
            <p class="text-sm text-gray-500 mb-4">Set to 0 for unlimited staff. Leave customer/order fields empty for unlimited.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Staff Limit -->
                <div>
                    <x-input-label for="staff_limit" :value="__('Staff Limit')" />
                    <x-text-input id="staff_limit" name="staff_limit" type="number" min="0" class="mt-1 block w-full" :value="old('staff_limit', $plan->staff_limit ?? 1)" required />
                    <x-input-error :messages="$errors->get('staff_limit')" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500">0 = Unlimited</p>
                </div>

                <!-- Customer Limit -->
                <div>
                    <x-input-label for="customer_limit" :value="__('Customer Limit')" />
                    <x-text-input id="customer_limit" name="customer_limit" type="number" min="1" class="mt-1 block w-full" :value="old('customer_limit', $plan->customer_limit ?? '')" placeholder="Empty = Unlimited" />
                    <x-input-error :messages="$errors->get('customer_limit')" class="mt-2" />
                </div>

                <!-- Order Limit -->
                <div>
                    <x-input-label for="order_limit" :value="__('Orders/Month Limit')" />
                    <x-text-input id="order_limit" name="order_limit" type="number" min="1" class="mt-1 block w-full" :value="old('order_limit', $plan->order_limit ?? '')" placeholder="Empty = Unlimited" />
                    <x-input-error :messages="$errors->get('order_limit')" class="mt-2" />
                </div>
            </div>
        </div>
    </div>

    <!-- Features -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Included Features</h3>
            <p class="text-sm text-gray-500 mb-4">Select which features are included in this plan.</p>

            @php $planFeatures = old('features', $plan->features ?? []); @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ($availableFeatures as $featureKey => $featureDef)
                    <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input
                            type="checkbox"
                            name="features[]"
                            value="{{ $featureKey }}"
                            class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            {{ in_array($featureKey, $planFeatures) ? 'checked' : '' }}
                        >
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $featureDef['label'] }}</div>
                            <div class="text-xs text-gray-500">{{ $featureDef['description'] }}</div>
                            @if (! empty($featureDef['requires']))
                                <div class="mt-1 text-[11px] font-medium text-amber-600">
                                    Requires:
                                    {{ collect($featureDef['requires'])->map(fn (string $feature): string => config("themes.features.{$feature}.label", $feature))->implode(', ') }}
                                </div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <x-input-error :messages="$errors->get('features')" class="mt-2" />
        </div>
    </div>

    <!-- Status -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status</h3>

            <div class="space-y-4">
                <label class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}
                    >
                    <div>
                        <span class="text-sm font-medium text-gray-900">Active</span>
                        <p class="text-xs text-gray-500">Inactive plans won't be available for new shops.</p>
                    </div>
                </label>

                <label class="flex items-center gap-3">
                    <input type="hidden" name="is_default" value="0">
                    <input
                        type="checkbox"
                        name="is_default"
                        value="1"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        {{ old('is_default', $plan->is_default ?? false) ? 'checked' : '' }}
                    >
                    <div>
                        <span class="text-sm font-medium text-gray-900">Default Plan</span>
                        <p class="text-xs text-gray-500">New shops will automatically be assigned this plan. Only one plan can be default.</p>
                    </div>
                </label>

                <!-- Sort Order -->
                <div class="max-w-xs">
                    <x-input-label for="sort_order" :value="__('Sort Order')" />
                    <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', $plan->sort_order ?? 0)" />
                    <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-4 ">
        <a href="{{ route('admin.subscription-plans.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        <button type="submit" class="inline-flex items-center px-4 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
            {{ $submitLabel ?? 'Save Plan' }}
        </button>
    </div>
</div>
