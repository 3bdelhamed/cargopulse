<?php

namespace App\Domains\Shipments\States;

class PickedUpState extends ShipmentState
{
    public static $name = 'picked_up';

    public function color(): string
    {
        return 'teal';
    }
}
