<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Staff Member</h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="max-w-2xl">
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('tenant.staff.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name', 'staff') border-red-300 @enderror">
                        @error('name', 'staff') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email', 'staff') border-red-300 @enderror">
                        @error('email', 'staff') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" required
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password', 'staff') border-red-300 @enderror">
                            @error('password', 'staff') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password_confirmation" required
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    @if ($canManageRoles && $assignableRoles->isNotEmpty())
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Roles</label>
                            <div class="space-y-3 rounded-md border border-gray-200 bg-gray-50 p-4">
                                @foreach ($assignableRoles as $role)
                                    <label class="flex items-start gap-3 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            name="roles[]"
                                            value="{{ $role->slug }}"
                                            @checked(collect(old('roles', []))->contains($role->slug))
                                            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        <span>
                                            <span class="block font-medium text-gray-800">{{ $role->name }}</span>
                                            @if ($role->description)
                                                <span class="block text-xs text-gray-500">{{ $role->description }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('roles', 'staff') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('roles.*', 'staff') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-5 py-2 text-sm font-medium text-white shadow-sm transition">
                            Add Staff
                        </button>
                        <a href="{{ route('tenant.staff.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-tenant-layout>
