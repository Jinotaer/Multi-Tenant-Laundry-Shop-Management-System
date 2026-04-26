<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\SendCustomerPasswordSetupRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('tenant.auth.forgot-password');
    }

    public function store(
        SendCustomerPasswordSetupRequest $request
    ): RedirectResponse {
        $email = $request->safe()->only('email');

        $broker = User::query()->where('email', $email['email'])->exists()
            ? 'users'
            : 'customers';

        $status = Password::broker($broker)->sendResetLink($email);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()
                ->withInput($request->safe()->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
