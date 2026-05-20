<?php

namespace App\Domains\Shipments\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ShipmentState extends State
{
    abstract public function color(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(PendingState::class)
            ->allowTransition(PendingState::class, ConfirmedState::class)
            ->allowTransition(ConfirmedState::class, PackedState::class)
            ->allowTransition(PackedState::class, AssignedState::class)
            ->allowTransition(ConfirmedState::class, AssignedState::class)
            ->allowTransition(AssignedState::class, InTransitState::class)
            ->allowTransition(InTransitState::class, DeliveredState::class)
            ->allowTransition(InTransitState::class, FailedState::class);
    }
}
