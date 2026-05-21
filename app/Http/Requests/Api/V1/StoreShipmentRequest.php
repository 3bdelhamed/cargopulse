<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Shipments\DTOs\ShipmentData;
use App\Domains\Tenants\Models\Merchant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
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
        $tenantId = $this->user()?->tenant_id;

        return [
            'merchant_id' => [
                'required',
                'integer',
                Rule::exists('merchants', 'id')->where('tenant_id', $tenantId),
            ],
            'tracking_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shipments', 'tracking_number')->where('tenant_id', $tenantId),
            ],
            'merchant_reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('shipments', 'merchant_reference')
                    ->where('tenant_id', $tenantId)
                    ->where('merchant_id', $this->integer('merchant_id')),
            ],
            'destination_address' => ['required', 'string', 'max:2000'],
            'pickup_address' => ['required', 'string', 'max:2000'],
            'cod_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tracking_number.unique' => 'Tracking number already exists.',
            'merchant_reference.unique' => 'Merchant reference already exists.',
            'merchant_id.exists' => 'Merchant does not exist for this tenant.',
        ];
    }

    public function toData(): ShipmentData
    {
        return ShipmentData::from($this->validated());
    }
}
