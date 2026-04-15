<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\SendCustomerPasswordSetupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Display the tenant customer forgot password view.
     */
    public function create(): View
    {
        return view('tenant.auth.forgot-password');
    }

    /**
     * Send a tenant customer password setup link.
     */
    public function store(
        SendCustomerPasswordSetupRequest $request
    ): RedirectResponse {
        $status = Password::broker('customers')->sendResetLink(
            $request->safe()->only('email'),
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()
                ->withInput($request->safe()->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
