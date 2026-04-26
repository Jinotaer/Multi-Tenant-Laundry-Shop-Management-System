<x-tenant-layout>
    @php
        $theme = tenant()->getThemePreset();
        $staffErrorBag = $errors->getBag('staff');
        $showStaffCreateModal = $staffErrorBag->isNotEmpty() && old('form_context') === 'staff-create';
        $requestedStaffEditId = $staffErrorBag->isNotEmpty() && old('form_context') === 'staff-edit'
            ? (int) old('editing_staff_id')
            : request()->integer('edit');
    @endphp

    <div class="tenant-page-stack space-y-4">
        <x-tenant-header title="Staff Management" description="Manage your team and their access." />

        @if (session('success'))
            <div class="rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="flex items-center gap-2 flex-1 max-w-lg">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z" />
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search staff name or email..."
                        class="w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm shadow-sm focus:outline-none focus:ring-1"
                    >
                </div>
                <button type="submit" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Search
                </button>
            </form>

            <div class="flex items-center gap-2">
                @if ($canAddStaff && $canCreateStaff)
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'staff-create-modal')"
                        class="inline-flex items-center gap-2 rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2 text-sm font-medium text-white shadow-sm transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Staff
                    </button>
                @elseif ($canCreateStaff)
                    <span class="inline-flex items-center gap-1 rounded-md border border-yellow-300 bg-yellow-50 px-4 py-2 text-sm text-yellow-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        Staff limit reached -
                        <a href="{{ route('tenant.subscription') }}" class="font-medium underline">Upgrade Plan</a>
                    </span>
                @endif
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            @if ($staff->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                    <p class="text-gray-500 text-sm font-medium">No staff members yet</p>
                    <p class="text-gray-400 text-xs mt-1">Add staff to help manage your laundry shop.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                @if ($hasRoleTables)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                @endif
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                @if ($canUpdateStaff || $canDeleteStaff)
                                    <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($staff as $member)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full {{ $theme['avatar_bg'] }} flex items-center justify-center">
                                                <span class="text-sm font-medium {{ $theme['avatar_text'] }}">{{ substr($member->name, 0, 1) }}</span>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ $member->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $member->email }}</td>
                                    @if ($hasRoleTables)
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            @php
                                                $displayRole = $member->roles->whereNotIn('slug', ['owner', 'customer', 'staff'])->first();
                                            @endphp

                                            @if ($displayRole)
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $displayRole->name }}</span>
                                            @else
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Staff</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ $member->created_at->format('M d, Y') }}</td>
                                    @if ($canUpdateStaff || $canDeleteStaff)
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            @if ($canUpdateStaff)
                                                <a
                                                    href="{{ route('tenant.staff.edit', $member) }}"
                                                    x-data
                                                    x-on:click.prevent="$dispatch('open-modal', 'staff-edit-{{ $member->id }}-modal')"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </a>
                                            @endif
                                            @if ($canDeleteStaff)
                                                <form method="POST" action="{{ route('tenant.staff.destroy', $member) }}" class="inline"
                                                    onsubmit="return confirm('Remove {{ $member->name }} from staff?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Remove</button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($staff->hasPages())
                    <div class="px-6 py-4 border-t">{{ $staff->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    @if ($canCreateStaff)
        @include('tenant.staff.partials.staff-create-modal', [
            'theme' => $theme,
            'assignableRoles' => $assignableRoles,
            'canManageRoles' => $canManageRoles,
            'showStaffCreateModal' => $showStaffCreateModal,
        ])
    @endif

    @if ($canUpdateStaff)
        @foreach ($staff as $member)
            @include('tenant.staff.partials.staff-edit-modal', [
                'theme' => $theme,
                'staffMember' => $member,
                'assignableRoles' => $assignableRoles,
                'canManageRoles' => $canManageRoles,
                'requestedStaffEditId' => $requestedStaffEditId,
            ])
        @endforeach

        @if ($editingStaff !== null && ! $staff->getCollection()->contains('id', $editingStaff->id))
            @include('tenant.staff.partials.staff-edit-modal', [
                'theme' => $theme,
                'staffMember' => $editingStaff,
                'assignableRoles' => $assignableRoles,
                'canManageRoles' => $canManageRoles,
                'requestedStaffEditId' => $requestedStaffEditId,
            ])
        @endif
    @endif
</x-tenant-layout>
