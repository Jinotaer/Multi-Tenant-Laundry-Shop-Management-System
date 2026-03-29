<?php

namespace App\Http\Requests\Tenant;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedTypes = array_keys(Service::availablePriceTypes());

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'bundle_items' => ['nullable', 'array'],
            'bundle_items.*.name' => ['nullable', 'string', 'max:255'],
            'bundle_items.*.qty' => ['nullable', 'integer', 'min:1'],
            'bundle_items.*.price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['required', Rule::in($allowedTypes)],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Prepare bundle items for validation.
     */
    protected function prepareForValidation(): void
    {
        $bundleItems = array_values(array_filter(
            (array) $this->input('bundle_items', []),
            fn (mixed $item): bool => is_array($item)
                && (filled($item['name'] ?? null) || filled($item['price'] ?? null))
        ));

        $this->merge([
            'bundle_items' => $bundleItems === [] ? null : $bundleItems,
        ]);
    }
}
