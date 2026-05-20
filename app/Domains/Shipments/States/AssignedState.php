<?php

namespace App\Domains\Shipments\States;

class AssignedState extends ShipmentState
{
    public static $name = 'assigned';

    public function color(): string
    {
        return 'purple';
    }
}
