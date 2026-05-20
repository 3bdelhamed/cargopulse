<?php

namespace App\Domains\Shipments\Actions;

use App\Domains\Shipments\DTOs\ShipmentData;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Shipments\States\PendingState;

class CreateShipmentAction
{
    public function execute(ShipmentData $data): Shipment
    {
        return Shipment::create([
            'merchant_id' => $data->merchant_id,
            'tracking_number' => $data->tracking_number,
            'state' => PendingState::$name,
            'delivery_address' => $data->destination_address,
            'pickup_address' => 'Pending',
            'cod_amount' => $data->cod_amount,
        ]);
    }
}
