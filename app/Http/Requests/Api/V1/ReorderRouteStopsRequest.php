<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Fleet\DTOs\ReorderRouteStopsData;
use Illuminate\Foundation\Http\FormRequest;

class ReorderRouteStopsRequest extends FormRequest
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
            'shipment_ids' => ['required', 'array', 'min:1'],
            'shipment_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function toData(): ReorderRouteStopsData
    {
        return ReorderRouteStopsData::from($this->validated());
    }
}
