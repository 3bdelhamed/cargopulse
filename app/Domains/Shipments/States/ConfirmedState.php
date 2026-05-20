<?php

namespace App\Domains\Shipments\States;

class ConfirmedState extends ShipmentState
{
    public static $name = 'confirmed';

    public function color(): string
    {
        return 'blue';
    }
}
