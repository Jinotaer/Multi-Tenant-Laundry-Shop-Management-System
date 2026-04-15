<x-modal name="staff-create-modal" :show="$showStaffCreateModal" maxWidth="2xl" focusable>
    <form method="POST" action="{{ route('tenant.staff.store') }}" class="p-6">
        @csrf
        <input type="hidden" name="form_context" value="staff-create">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Staff</p>
                <h3 class="mt-2 text-lg font-semibold text-gray-900">Add Staff</h3>
                <p class="mt-1 text-sm text-gray-500">Create a staff account and assign one or more roles.</p>
            </div>

            <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-5">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 @error('name', 'staff') border-red-300 @enderror"
                >
                @error('name', 'staff')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 @error('email', 'staff') border-red-300 @enderror"
                >
                @error('email', 'staff')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                    <input
                        type="password"
                        name="password"
                        required
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 @error('password', 'staff') border-red-300 @enderror"
                    >
                    @error('password', 'staff')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0"
                    >
                </div>
            </div>

            @if ($canManageRoles)
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Select Roles</label>
                    <div class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        @forelse ($assignableRoles as $role)
                            <label class="flex items-start gap-3 rounded-xl border border-transparent px-1 py-1 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $role->slug }}"
                                    @checked(collect(old('roles', []))->contains($role->slug))
                                    class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span>
                                    <span class="block font-medium text-gray-900">{{ $role->name }}</span>
                                    @if ($role->description)
                                        <span class="mt-0.5 block text-xs text-gray-500">{{ $role->description }}</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">No assignable roles yet. Create a role on the roles page first.</p>
                        @endforelse
                    </div>
                    @error('roles', 'staff')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('roles.*', 'staff')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                Save Staff
            </button>
        </div>
    </form>
</x-modal>
