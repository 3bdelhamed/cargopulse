<?php

namespace App\Domains\Shipments\Events;

use App\Domains\Shipments\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentDeliveredEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment
    ) {}
}
