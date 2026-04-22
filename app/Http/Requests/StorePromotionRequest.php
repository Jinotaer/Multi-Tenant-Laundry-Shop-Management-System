<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'discount_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'valid_until' => ['required', 'date', 'after:today'],
        ];
    }
}
