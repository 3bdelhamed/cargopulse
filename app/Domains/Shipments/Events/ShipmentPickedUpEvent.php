<?php

namespace App\Domains\Shipments\Events;

use App\Domains\Shipments\Models\Shipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentPickedUpEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shipment $shipment
    ) {}
}
