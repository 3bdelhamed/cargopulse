<?php

namespace App\Domains\Shipments\Models;

use App\Traits\BelongsToTenant;
use App\Domains\Shipments\States\ShipmentState;
use App\Domains\Shipments\States\PendingState;
use App\Domains\Shipments\States\ConfirmedState;
use App\Domains\Shipments\States\InTransitState;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;

class Shipment extends Model
{
    use HasStates, BelongsToTenant;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => ShipmentState::class,
    ];

    protected function registerStates(): void
    {
        $this->addState('status', ShipmentState::class)
            ->default(PendingState::class)
            ->allowTransition(PendingState::class, ConfirmedState::class)
            ->allowTransition(ConfirmedState::class, InTransitState::class)
            ->allowTransition(InTransitState::class, DeliveredState::class)
            ->allowTransition(InTransitState::class, FailedState::class);
    }
}
