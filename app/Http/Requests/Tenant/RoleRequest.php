<?php

namespace App\Http\Requests\Tenant;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    protected $errorBag = 'role';

    public function authorize(): bool
    {
        $user = $this->user();

        if (
            $user === null
            || ! Schema::hasTable('roles')
            || ! Schema::hasTable('permission_role')
        ) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($this->isMethod('POST')) {
            return $user->hasPermission('roles.create');
        }

        return $user->hasPermission('roles.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'key')],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $slug = Str::slug((string) $this->input('name'));
                $currentRole = $this->route('role');

                if ($slug === '') {
                    $validator->errors()->add('name', 'Enter a role name that includes letters or numbers.');

                    return;
                }

                $query = Role::query()->where('slug', $slug);

                if ($currentRole instanceof Role) {
                    $query->whereKeyNot($currentRole->getKey());
                }

                if ($query->exists()) {
                    $validator->errors()->add('name', 'A role with this name already exists.');
                }
            },
        ];
    }
}
