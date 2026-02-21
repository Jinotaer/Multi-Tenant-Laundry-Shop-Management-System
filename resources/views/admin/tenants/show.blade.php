<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Shop: {{ $tenant->data['shop_name'] ?? $tenant->id }}
            </h2>
            <a href="{{ route('admin.tenants.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to list</a>
        </div>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getAdminTheme(); @endphp

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tenant Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Shop Details</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tenant ID</dt>
                                <dd class="text-sm text-gray-900">{{ $tenant->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Shop Name</dt>
                                <dd class="text-sm text-gray-900">{{ $tenant->data['shop_name'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    @if ($tenant->isEnabled())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Disabled</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="text-sm text-gray-900">{{ $tenant->created_at->format('F d, Y h:i A') }}</dd>
                            </div>
                        </dl>

                        <!-- Toggle Status -->
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}">
                                @csrf
                                @method('PATCH')
                                @if ($tenant->isEnabled())
                                    <x-secondary-button type="submit" onclick="return confirm('Disable this shop? Users will not be able to access it.')">
                                        {{ __('Disable Shop') }}
                                    </x-secondary-button>
                                @else
                                    <x-primary-button type="submit" onclick="return confirm('Enable this shop?')">
                                        {{ __('Enable Shop') }}
                                    </x-primary-button>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Owner Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Owner Information</h3>
                        @if ($tenant->registration)
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Owner Name</dt>
                                    <dd class="text-sm text-gray-900">{{ $tenant->registration->owner_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Owner Email</dt>
                                    <dd class="text-sm text-gray-900">{{ $tenant->registration->owner_email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Registered</dt>
                                    <dd class="text-sm text-gray-900">{{ $tenant->registration->created_at->format('F d, Y h:i A') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Approved</dt>
                                    <dd class="text-sm text-gray-900">{{ $tenant->registration->approved_at?->format('F d, Y h:i A') ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        @else
                            <p class="text-sm text-gray-500">Registration data not available.</p>
                        @endif
                    </div>
                </div>

                <!-- Subscription Plan -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Subscription Plan</h3>
                        @if ($tenant->subscriptionPlan)
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Plan</dt>
                                    <dd class="text-sm text-gray-900">
                                        {{ $tenant->subscriptionPlan->name }}
                                        @if ($tenant->subscriptionPlan->is_default)
                                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Default</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Price</dt>
                                    <dd class="text-sm text-gray-900">{{ $tenant->subscriptionPlan->formatted_price }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Staff Limit</dt>
                                    <dd class="text-sm text-gray-900">{{ $tenant->subscriptionPlan->staff_limit_display }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Customer Limit</dt>
                                    <dd class="text-sm text-gray-900">{{ $tenant->subscriptionPlan->customer_limit_display }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Order Limit</dt>
                                    <dd class="text-sm text-gray-900">{{ $tenant->subscriptionPlan->order_limit_display }}</dd>
                                </div>
                            </dl>
                        @else
                            <p class="text-sm text-gray-500 mb-4">No plan assigned.</p>
                        @endif

                        {{-- Trial Status --}}
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Trial & Payment Status</h4>
                            <div class="flex flex-wrap gap-2">
                                @if($tenant->is_paid)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @elseif($tenant->isOnTrial())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        On Trial &mdash; {{ $tenant->trialDaysRemaining() }} days left
                                    </span>
                                @elseif($tenant->isTrialExpired())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Trial Expired</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">No trial set</span>
                                @endif
                            </div>
                            @if($tenant->trial_ends_at)
                                <p class="text-xs text-gray-400 mt-1">Trial {{ $tenant->isTrialExpired() ? 'expired' : 'expires' }}: {{ $tenant->trial_ends_at->format('M d, Y') }}</p>
                            @endif

                            {{-- Mark as Paid --}}
                            @if(!$tenant->is_paid)
                                <form method="POST" action="{{ route('admin.tenants.mark-paid', $tenant) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('Mark this tenant as paid? This will remove trial restrictions.')" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-md uppercase tracking-widest transition">
                                        Mark as Paid
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.tenants.mark-unpaid', $tenant) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('Revoke paid status? Trial restrictions will apply again.')" class="inline-flex items-center px-3 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded-md uppercase tracking-widest transition">
                                        Revoke Paid Status
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- Change Plan --}}
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <form method="POST" action="{{ route('admin.tenants.update-plan', $tenant) }}">
                                @csrf
                                @method('PATCH')
                                <label class="block text-sm font-medium text-gray-500 mb-2">Change Plan</label>
                                <div class="flex items-center gap-2">
                                    <select name="subscription_plan_id" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}" {{ $tenant->subscription_plan_id == $plan->id ? 'selected' : '' }}>
                                                {{ $plan->name }} ({{ $plan->formatted_price }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="inline-flex items-center px-3 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} text-white text-xs font-semibold rounded-md uppercase tracking-widest transition">
                                        Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Domains / Access Portal -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Access Portal</h3>
                        @if ($tenant->domains->isEmpty())
                            <p class="text-sm text-gray-500">No domains configured.</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($tenant->domains as $domain)
                                    <li class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $domain->domain }}
                                            </span>
                                        </div>
                                        <a href="http://{{ $domain->domain }}:8000" target="_blank" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-900">
                                            Open Portal
                                            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($tenant->isDisabled())
                                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm text-yellow-700">This shop is currently <strong>disabled</strong>. Users cannot access the portal.</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Additional Data -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Tenant Data</h3>
                        @if (!empty($tenant->data))
                            <dl class="space-y-3">
                                @foreach ($tenant->data as $key => $value)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                        <dd class="text-sm text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-sm text-gray-500">No additional data.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Feature Flags -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Feature Flags</h3>
                    <p class="text-sm text-gray-500 mb-6">Toggle features available to this shop.</p>

                    <form method="POST" action="{{ route('admin.tenants.update-features', $tenant) }}">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-4">
                            @foreach (config('themes.features') as $featureKey => $featureDef)
                                <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="features[]"
                                        value="{{ $featureKey }}"
                                        class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        {{ in_array($featureKey, $tenant->features ?? []) ? 'checked' : '' }}
                                    >
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $featureDef['label'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $featureDef['description'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                                {{ __('Save Features') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-red-200">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-red-600 mb-2">Danger Zone</h3>
                    <p class="text-sm text-gray-500 mb-4">Deleting this shop will permanently remove all its data including customers, orders, and users.</p>

                    <x-danger-button x-data="" x-on:click="$dispatch('open-modal', 'confirm-shop-deletion')">
                        {{ __('Delete Shop') }}
                    </x-danger-button>

                    <x-modal name="confirm-shop-deletion" focusable>
                        <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" class="p-6">
                            @csrf
                            @method('DELETE')

                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Are you sure you want to delete this shop?') }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Once deleted, all data for') }} <strong>{{ $tenant->data['shop_name'] ?? $tenant->id }}</strong> {{ __('will be permanently removed.') }}
                            </p>

                            <div class="mt-6 flex justify-end">
                                <x-secondary-button x-on:click="$dispatch('close')">
                                    {{ __('Cancel') }}
                                </x-secondary-button>

                                <x-danger-button class="ms-3">
                                    {{ __('Delete Shop') }}
                                </x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
