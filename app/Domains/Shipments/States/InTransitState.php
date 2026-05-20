<?php

namespace App\Domains\Shipments\States;

class InTransitState extends ShipmentState
{
    public static $name = 'in_transit';

    public function color(): string
    {
        return 'yellow';
    }
}
