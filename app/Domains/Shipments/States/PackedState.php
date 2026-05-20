<?php

namespace App\Domains\Shipments\States;

class PackedState extends ShipmentState
{
    public static $name = 'packed';

    public function color(): string
    {
        return 'orange';
    }
}
