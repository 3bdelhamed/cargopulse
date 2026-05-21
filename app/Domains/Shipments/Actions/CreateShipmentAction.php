<?php

namespace App\Domains\Shipments\Actions;

use App\Domains\Shipments\DTOs\ShipmentData;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Shipments\States\PendingState;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CreateShipmentAction
{
    public function execute(ShipmentData $data): Shipment
    {
        try {
            return Shipment::create([
                'merchant_id' => $data->merchant_id,
                'tracking_number' => $data->tracking_number,
                'merchant_reference' => $data->merchant_reference,
                'state' => PendingState::$name,
                'delivery_address' => $data->destination_address,
                'pickup_address' => $data->pickup_address,
                'cod_amount' => $data->cod_amount,
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                if (str_contains((string) $exception->getMessage(), 'merchant_reference')) {
                    throw ValidationException::withMessages([
                        'merchant_reference' => ['Merchant reference already exists.'],
                    ]);
                }

                throw ValidationException::withMessages([
                    'tracking_number' => ['Tracking number already exists.'],
                ]);
            }

            throw $exception;
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
