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
    /**
     * Display the tenant customer password reset view.
     */
    public function create(Request $request): View
    {
        return view('tenant.auth.reset-password', ['request' => $request]);
    }

    /**
     * Persist the tenant customer's new password.
     */
    public function store(
        StoreCustomerPasswordRequest $request
    ): RedirectResponse {
        $status = Password::broker('customers')->reset(
            $request->safe()->only(
                'email',
                'password',
                'password_confirmation',
                'token',
            ),
            function ($customer) use ($request): void {
                $customer
                    ->forceFill([
                        'password' => Hash::make($request->validated('password')),
                        'remember_token' => Str::random(60),
                    ])
                    ->save();

                event(new PasswordReset($customer));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('tenant.login')->with('status', __($status))
            : back()
                ->withInput($request->safe()->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
