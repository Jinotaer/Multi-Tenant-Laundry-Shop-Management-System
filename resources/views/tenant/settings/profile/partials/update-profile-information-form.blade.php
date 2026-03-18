<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-slate-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form method="post" action="{{ route('tenant.settings.profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <input id="name" name="name" type="text" class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-gray-900 shadow-sm {{ $theme['focus_ring'] }} focus:border-transparent focus:ring-2 focus:ring-offset-0 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-400" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <input id="email" name="email" type="email" class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-gray-900 shadow-sm {{ $theme['focus_ring'] }} focus:border-transparent focus:ring-2 focus:ring-offset-0 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-400" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="tenant-primary-action inline-flex items-center rounded-md border px-4 py-2 font-semibold text-xs uppercase tracking-widest">
                {{ __('Save') }}
            </button>

            @if (session('success') === 'Profile updated successfully.')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-slate-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
