<?php

namespace App\Domains\Shipments\States;

class PendingState extends ShipmentState
{
    public static $name = 'pending';

    public function color(): string
    {
        return 'gray';
    }
}
