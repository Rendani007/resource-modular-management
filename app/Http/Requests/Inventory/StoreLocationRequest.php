<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = app('tenant_id');

        return [
            'code' => [
                'required','string','max:50',
                Rule::unique('inventory_locations','code')
                    ->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required','string','max:255'],
        ];
    }
}
