<x-modal name="role-create-modal" :show="$showRoleCreateModal" maxWidth="2xl" focusable>
    <form
        method="POST"
        action="{{ route('tenant.roles.store') }}"
        class="p-6"
        x-data="{
            selectedPermissions: @js(collect(old('permissions', []))->values()->all()),
            toggleModule(permissionKeys, checked) {
                if (checked) {
                    this.selectedPermissions = [...new Set([...this.selectedPermissions, ...permissionKeys])];

                    return;
                }

                this.selectedPermissions = this.selectedPermissions.filter((permissionKey) => ! permissionKeys.includes(permissionKey));
            },
            moduleSelected(permissionKeys) {
                return permissionKeys.length > 0 && permissionKeys.every((permissionKey) => this.selectedPermissions.includes(permissionKey));
            },
            modulePartiallySelected(permissionKeys) {
                const selectedCount = permissionKeys.filter((permissionKey) => this.selectedPermissions.includes(permissionKey)).length;

                return selectedCount > 0 && selectedCount < permissionKeys.length;
            },
        }"
    >
        @csrf
        <input type="hidden" name="form_context" value="role-create">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Roles</p>
                <h3 class="mt-2 text-lg font-semibold text-gray-900">Add Role</h3>
                <p class="mt-1 text-sm text-gray-500">Set the role name, description, and permissions for staff assignments.</p>
            </div>

            <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-5">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Role Name <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 @error('name', 'role') border-red-300 @enderror"
                >
                @error('name', 'role')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                <textarea
                    name="description"
                    rows="3"
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 @error('description', 'role') border-red-300 @enderror"
                >{{ old('description') }}</textarea>
                @error('description', 'role')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Select Permissions</label>
                <div class="max-h-96 space-y-4 overflow-y-auto rounded-2xl border border-gray-200 bg-gray-50 p-4">
                    @forelse ($rolePermissionsByModule as $module => $permissions)
                        <div>
                            @php($modulePermissionKeys = $permissions->pluck('key')->values()->all())
                            <div class="mb-2">
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        x-bind:checked="moduleSelected(@js($modulePermissionKeys))"
                                        x-on:change="toggleModule(@js($modulePermissionKeys), $event.target.checked)"
                                        x-effect="$el.indeterminate = modulePartiallySelected(@js($modulePermissionKeys))"
                                        class="rounded border-gray-300"
                                    >
                                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">{{ str_replace('_', ' ', $module) }}</span>
                                </label>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($permissions as $permission)
                                    <label class="ml-7 flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->key }}"
                                            @checked(collect(old('permissions', []))->contains($permission->key))
                                            x-model="selectedPermissions"
                                            class="rounded border-gray-300"
                                        >
                                        <span>{{ $permission->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No permissions configured yet.</p>
                    @endforelse
                </div>
                @error('permissions', 'role')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @error('permissions.*', 'role')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                Save Role
            </button>
        </div>
    </form>
</x-modal>
