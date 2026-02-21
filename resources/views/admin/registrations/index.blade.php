<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Shop Registrations') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($registrations->isEmpty())
                        <p class="text-gray-500">No registration applications yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subdomain</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th> -->
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($registrations as $registration)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $registration->shop_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $registration->subdomain }}.localhost:8000</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div>{{ $registration->owner_name }}</div>
                                                <div class="text-xs text-gray-400">{{ $registration->owner_email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($registration->isPending())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                                @elseif ($registration->isApproved())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                                @endif
                                            </td>
                                            <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $registration->created_at->diffForHumans() }}</td> -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if ($registration->isPending())
                                                    <div class="flex items-center space-x-2">
                                                        <form method="POST" action="/admin/registrations/{{ $registration->id }}/approve">
                                                            @csrf
                                                            <x-primary-button type="submit" onclick="return confirm('Approve this shop registration? A new tenant database and domain will be created.')">
                                                                {{ __('Approve') }}
                                                            </x-primary-button>
                                                        </form>

                                                        <x-danger-button x-data x-on:click="$dispatch('open-modal', 'reject-registration-{{ $registration->id }}')">
                                                            {{ __('Reject') }}
                                                        </x-danger-button>

                                                        <x-modal name="reject-registration-{{ $registration->id }}" focusable>
                                                            <form method="POST" action="/admin/registrations/{{ $registration->id }}/reject" class="p-6">
                                                                @csrf

                                                                <h2 class="text-lg font-medium text-gray-900">
                                                                    {{ __('Reject Registration') }}
                                                                </h2>

                                                                <p class="mt-1 text-sm text-gray-600">
                                                                    {{ __('Rejecting') }} <strong>{{ $registration->shop_name }}</strong> {{ __('by') }} {{ $registration->owner_name }}.
                                                                </p>

                                                                <div class="mt-6">
                                                                    <x-input-label for="rejection_reason_{{ $registration->id }}" value="{{ __('Reason (optional)') }}" />
                                                                    <textarea
                                                                        id="rejection_reason_{{ $registration->id }}"
                                                                        name="rejection_reason"
                                                                        rows="3"
                                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                                        placeholder="Provide a reason for rejection..."
                                                                    ></textarea>
                                                                </div>

                                                                <div class="mt-6 flex justify-end">
                                                                    <x-secondary-button x-on:click="$dispatch('close')">
                                                                        {{ __('Cancel') }}
                                                                    </x-secondary-button>

                                                                    <x-danger-button class="ms-3">
                                                                        {{ __('Reject') }}
                                                                    </x-danger-button>
                                                                </div>
                                                            </form>
                                                        </x-modal>
                                                    </div>
                                                @elseif ($registration->isApproved())
                                                    <span class="text-xs text-gray-400">Approved {{ $registration->approved_at->diffForHumans() }}</span>
                                                @else
                                                    <span class="text-xs text-gray-400" title="{{ $registration->rejection_reason }}">Rejected {{ $registration->rejected_at->diffForHumans() }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $registrations->links() }}
                        </div>
                    @endif
                </div>
            </div>
</x-admin-layout>
