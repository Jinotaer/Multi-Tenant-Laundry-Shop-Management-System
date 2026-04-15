<?php

namespace App\Http\Requests\Tenant;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffRequest extends FormRequest
{
    protected $errorBag = 'staff';

    protected function prepareForValidation(): void
    {
        Permission::ensureDefaultsExist();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($this->isMethod('POST')) {
            return $user->hasPermission('staff.create');
        }

        return $user->hasPermission('staff.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('staff')?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
        ];

        if ($this->isMethod('POST')) {
            $rules['password'] = ['required', 'string', Password::min(8), 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', Password::min(8), 'confirmed'];
        }

        if (Schema::hasTable('roles') && Schema::hasTable('role_user')) {
            $rules['roles'] = ['sometimes', 'array'];
            $rules['roles.*'] = [
                'string',
                Rule::exists('roles', 'slug'),
                Rule::notIn(['owner', 'customer', 'staff']),
            ];
        }

        return $rules;
    }
}
