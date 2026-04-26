<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\StoreCustomerPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('tenant.auth.reset-password', ['request' => $request]);
    }

    public function store(
        StoreCustomerPasswordRequest $request
    ): RedirectResponse {
        $credentials = $request->safe()->only(
            'email',
            'password',
            'password_confirmation',
            'token',
        );

        $callback = function ($user) use ($request): void {
            $user->forceFill([
                'password' => Hash::make($request->validated('password')),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        };

        // Try users broker first (owner/staff), fall back to customers
        $status = Password::broker('users')->reset($credentials, $callback);

        if ($status !== Password::PASSWORD_RESET) {
            $status = Password::broker('customers')->reset($credentials, $callback);
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('tenant.login')->with('status', __($status))
            : back()
                ->withInput($request->safe()->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
