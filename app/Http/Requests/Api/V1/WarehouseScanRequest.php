<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Warehouses\DTOs\ScanData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'max:255'],
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('tenant_id', $this->user()?->tenant_id),
            ],
        ];
    }

    public function toData(): ScanData
    {
        return ScanData::from($this->validated());
    }
}
