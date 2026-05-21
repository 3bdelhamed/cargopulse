<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'merchant_reference' => $this->merchant_reference,
            'merchant_id' => $this->merchant_id,
            'driver_id' => $this->driver_id,
            'route_id' => $this->route_id,
            'route_sequence' => $this->route_sequence,
            'state' => $this->state?->getValue(),
            'priority' => $this->priority,
            'pickup_address' => $this->pickup_address,
            'delivery_address' => $this->delivery_address,
            'cod_amount' => $this->cod_amount === null ? null : (float) $this->cod_amount,
            'delivery_fee' => $this->delivery_fee === null ? null : (float) $this->delivery_fee,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
