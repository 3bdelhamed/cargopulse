<?php

namespace App\Http\Requests\Api\V1;

use App\Domains\Shipments\States\AssignedState;
use App\Domains\Shipments\States\ConfirmedState;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use App\Domains\Shipments\States\InTransitState;
use App\Domains\Shipments\States\PackedState;
use App\Domains\Shipments\States\PendingState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchShipmentsRequest extends FormRequest
{
    public const MAX_PER_PAGE = 100;

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
            'state' => ['nullable', 'string', Rule::in(self::states())],
            'merchant_id' => [
                'nullable',
                'integer',
                Rule::exists('merchants', 'id')->where('tenant_id', $this->user()?->tenant_id),
            ],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['nullable', 'string', Rule::in(self::sortColumns())],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function sortColumns(): array
    {
        return ['created_at', 'updated_at', 'route_sequence', 'delivery_fee', 'tracking_number'];
    }

    /**
     * @return list<string>
     */
    public static function states(): array
    {
        return [
            PendingState::$name,
            ConfirmedState::$name,
            PackedState::$name,
            AssignedState::$name,
            InTransitState::$name,
            DeliveredState::$name,
            FailedState::$name,
        ];
    }
}
