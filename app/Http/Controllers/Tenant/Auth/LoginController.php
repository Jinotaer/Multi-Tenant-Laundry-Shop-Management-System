<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Display the tenant login view.
     */
    public function create(): View
    {
        return view('tenant.auth.login');
    }

    /**
     * Handle an incoming tenant authentication request.
     * Supports login for owner/staff users and customers.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('email', 'password');
        $rememberMe = $request->boolean('remember');

        $user = User::query()
            ->where('email', $credentials['email'])
            ->whereIn('role', ['owner', 'staff'])
            ->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::guard('web')->login($user, $rememberMe);
            $request->session()->regenerate();

            return redirect()->intended(route('tenant.dashboard'));
        }

        $customer = Customer::query()
            ->where('email', $credentials['email'])
            ->first();

        if ($customer && Hash::check($credentials['password'], $customer->password)) {
            Auth::guard('customer')->login($customer, $rememberMe);
            $request->session()->regenerate();

            return redirect()->intended(route('tenant.dashboard'));
        }

        return back()->withErrors([
            'email' => __('The provided credentials do not match our records.'),
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated tenant session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            Auth::guard('customer')->logout();
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}
