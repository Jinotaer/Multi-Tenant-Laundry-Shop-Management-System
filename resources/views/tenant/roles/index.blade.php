<x-tenant-layout>
    @php
        $theme = tenant()->getThemePreset();
        $roleErrorBag = $errors->getBag('role');
        $showRoleCreateModal = $roleErrorBag->isNotEmpty() && old('form_context') === 'role-create';
    @endphp

    <div class="space-y-4">
        <x-tenant-header title="Role Management" description="Configure staff roles and permissions.">
            @if (!empty($canCreateRoles) && $canCreateRoles)
                <x-slot name="actions">
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'role-create-modal')"
                        class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition"
                        style="background: var(--tenant-theme-accent);">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Role
                    </button>
                </x-slot>
            @endif
        </x-tenant-header>

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if (! $hasRoleTables)
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                Role tables are not available for this tenant yet. Run tenant migrations before creating roles.
            </div>
        @else
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Roles</h3>
                    <p class="text-sm text-gray-500">Create reusable access groups, then assign them when adding staff.</p>
                </div>

                @if ($canCreateRoles)
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'role-create-modal')"
                            class="inline-flex items-center gap-2 rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2 text-sm font-medium text-white shadow-sm transition"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Role
                        </button>
                    </div>
                @endif
            </div>

            @if ($roles->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-14 text-center shadow-sm">
                    <svg class="mx-auto mb-4 h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9A2.25 2.25 0 015.25 16.5v-9A2.25 2.25 0 017.5 5.25h9A2.25 2.25 0 0118.75 7.5v9A2.25 2.25 0 0116.5 18.75z" />
                    </svg>
                    <p class="text-gray-500 text-sm font-medium">No roles yet</p>
                    <p class="text-gray-400 text-xs mt-1">Create a role to group permissions for your staff.</p>
                </div>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ($roles as $role)
                        @php
                            $isConfiguredRole = in_array($role->slug, $configuredRoleSlugs, true);
                        @endphp

                        <article class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">{{ $role->name }}</h3>
                                        <p class="mt-1 text-sm text-gray-500">{{ $role->description ?: 'No description provided.' }}</p>
                                        <p class="mt-1 text-xs text-gray-400">{{ $role->users->count() }} staff assigned</p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        @if ($canUpdateRoles && $editableRoleIds->contains($role->id))
                                            <button
                                                type="button"
                                                x-data
                                                x-on:click="$dispatch('open-modal', 'role-edit-{{ $role->id }}-modal')"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                            >
                                                Edit
                                            </button>
                                        @endif

                                        @if ($canDeleteRoles && $deletableRoleIds->contains($role->id) && ! $isConfiguredRole)
                                            <form method="POST" action="{{ route('tenant.roles.destroy', $role) }}" onsubmit="return confirm('Remove {{ $role->name }} role?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-900">Remove</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 px-6 py-5">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 px-4 py-3">
                                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Assigned Staff</p>
                                        <p class="mt-1 text-xl font-semibold text-gray-900">{{ $role->users->count() }}</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 px-4 py-3">
                                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Permissions</p>
                                        <p class="mt-1 text-xl font-semibold text-gray-900">{{ $role->permissions->count() }}</p>
                                    </div>
                                </div>

                                <div>
                                    <p class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-400">Permission Set</p>
                                    @if ($role->permissions->isEmpty())
                                        <p class="text-sm text-gray-500">No permissions assigned yet.</p>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($role->permissions as $permission)
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $permission->label }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    @if ($hasRoleTables && $canCreateRoles)
        @include('tenant.roles.partials.role-create-modal', [
            'theme' => $theme,
            'rolePermissionsByModule' => $rolePermissionsByModule,
            'showRoleCreateModal' => $showRoleCreateModal,
        ])
    @endif

    @if ($hasRoleTables && $canUpdateRoles)
        @foreach ($roles as $role)
            @if ($editableRoleIds->contains($role->id))
                @include('tenant.roles.partials.role-edit-modal', [
                    'theme' => $theme,
                    'role' => $role,
                    'rolePermissionsByModule' => $rolePermissionsByModule,
                ])
            @endif
        @endforeach
    @endif
</x-tenant-layout>
