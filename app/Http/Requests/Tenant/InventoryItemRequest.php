<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class InventoryItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isOwner();
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
            'sku' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'quantity_on_hand' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
