<?php

namespace App\Domains\Shipments\States;

class DeliveredState extends ShipmentState
{
    public static $name = 'delivered';

    public function color(): string
    {
        return 'green';
    }

    /**
     * NOTE: Spatie Laravel Model States fires a StateChanged event automatically,
     * but we hook into the Eloquent `saved` event on the Model to fire 
     * our custom domain event.
     * 
     * See App\Domains\Shipments\Models\Shipment::booted() for the implementation:
     * 
     * static::saved(function (Shipment $shipment) {
     *     if ($shipment->wasChanged('state') && $shipment->state instanceof DeliveredState) {
     *         event(new \App\Domains\Shipments\Events\ShipmentDeliveredEvent($shipment));
     *     }
     * });
     */
}
