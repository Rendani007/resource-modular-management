<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = app('tenant_id');
        $locId = $this->route('location')->id ?? null;

        return [
            'code' => [
                'required','string','max:50',
                Rule::unique('inventory_locations','code')
                    ->where(fn($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($locId),
            ],
            'name' => ['required','string','max:255'],
        ];
    }
}
