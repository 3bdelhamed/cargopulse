<?php

namespace App\Domains\Shipments\States;

class FailedState extends ShipmentState
{
    public static $name = 'failed';

    public function color(): string
    {
        return 'red';
    }
}
