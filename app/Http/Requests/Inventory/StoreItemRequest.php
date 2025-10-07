<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool { return true; } // route/middleware handles auth

    public function rules(): array
    {
        $tenantId = app('tenant_id');

        return [
            'sku' => [
                'required','string','max:50',
                Rule::unique('inventory_items','sku')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required','string','max:255'],
            'category' => ['nullable','string','max:100'],
            'uom' => ['required','string','max:20'],
            'reorder_level' => ['nullable','integer','min:0'],
            'metadata' => ['nullable','array'],
        ];
    }
}
