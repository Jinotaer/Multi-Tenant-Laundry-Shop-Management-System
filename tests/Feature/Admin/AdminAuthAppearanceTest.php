<?php

it('renders the admin login page with the themed auth shell', function () {
    $defaults = config('admin-layout.defaults');

    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('Admin Portal')
        ->assertSee('tenant-auth-shell', false)
        ->assertSee('tenant-auth-card', false)
        ->assertSee('tenant-auth-submit', false)
        ->assertSee('data-theme="'.$defaults['theme'].'"', false)
        ->assertSee('data-color-mode="'.$defaults['color_mode'].'"', false)
        ->assertSee('data-font-size="'.$defaults['font_size'].'"', false)
        ->assertSee('data-border-radius="'.$defaults['border_radius'].'"', false);
});

it('renders the admin password recovery pages with the themed auth shell', function () {
    $this->get(route('admin.password.request'))
        ->assertOk()
        ->assertSee('Reset admin password')
        ->assertSee('tenant-auth-shell', false)
        ->assertSee('tenant-auth-submit', false);

    $this->get(route('admin.password.reset', ['token' => 'reset-token']))
        ->assertOk()
        ->assertSee('Choose a new admin password')
        ->assertSee('tenant-auth-shell', false)
        ->assertSee('tenant-auth-submit', false);
});

it('renders the remaining admin auth views with the admin guest shell', function () {
    $verifyEmailView = file_get_contents(resource_path('views/admin/auth/verify-email.blade.php'));
    $confirmPasswordView = file_get_contents(resource_path('views/admin/auth/confirm-password.blade.php'));

    expect($verifyEmailView)
        ->toContain('<x-admin-guest-layout>')
        ->toContain('Verify your email')
        ->toContain('tenant-auth-submit');

    expect($confirmPasswordView)
        ->toContain('<x-admin-guest-layout>')
        ->toContain('Confirm your password')
        ->toContain('tenant-auth-submit');
});

it('renders a dark mode toggle on the admin login page', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('Switch to dark mode', false)
        ->assertSee('x-show="!isDark"', false)
        ->assertSee('x-show="isDark"', false);
});

it('includes dark mode classes on the admin guest layout', function () {
    $layout = file_get_contents(resource_path('views/layouts/admin-guest.blade.php'));

    expect($layout)
        ->toContain('dark:border-slate-700')
        ->toContain('dark:bg-slate-800/80')
        ->toContain('dark:text-slate-400')
        ->toContain('toggle()');
});

it('includes dark mode classes on shared components', function () {
    $inputError = file_get_contents(resource_path('views/components/input-error.blade.php'));
    $sessionStatus = file_get_contents(resource_path('views/components/auth-session-status.blade.php'));

    expect($inputError)->toContain('dark:text-red-400');
    expect($sessionStatus)->toContain('dark:text-green-400');
});
