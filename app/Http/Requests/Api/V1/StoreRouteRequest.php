<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Fleet\DTOs\RouteAssignmentData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRouteRequest extends FormRequest
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
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('drivers', 'id')->where('tenant_id', $tenantId),
            ],
            'dispatch_date' => ['required', 'date'],
            'shipment_ids' => ['required', 'array', 'min:1'],
            'shipment_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('shipments', 'id')->where('tenant_id', $tenantId),
            ],
        ];
    }

    public function toData(): RouteAssignmentData
    {
        return RouteAssignmentData::from($this->validated());
    }
}
