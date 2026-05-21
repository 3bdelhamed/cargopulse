<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Fleet\DTOs\DriverLocationData;
use Illuminate\Foundation\Http\FormRequest;

class StoreDriverLocationRequest extends FormRequest
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
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'timestamp' => ['required', 'integer', 'min:0'],
        ];
    }

    public function toData(): DriverLocationData
    {
        return DriverLocationData::from($this->validated());
    }
}
