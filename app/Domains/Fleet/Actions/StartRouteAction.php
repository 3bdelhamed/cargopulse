<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\States\InTransitState;
use App\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

class StartRouteAction
{
    public function execute(Route $route): Route
    {
        if ($route->status !== Route::STATUS_DRAFT) {
            throw new DomainRuleException('Only draft routes can be started.');
        }

        return DB::transaction(function () use ($route) {
            $route = Route::whereKey($route->id)->lockForUpdate()->firstOrFail();

            if ($route->status !== Route::STATUS_DRAFT) {
                throw new DomainRuleException('Only draft routes can be started.');
            }

            if ($route->shipments()->count() === 0) {
                throw new DomainRuleException('Route must contain at least one shipment.');
            }

            $route->update(['status' => Route::STATUS_IN_PROGRESS]);

            // Automatically transition all assigned shipments
            foreach ($route->shipments as $shipment) {
                if ($shipment->state->canTransitionTo(\App\Domains\Shipments\States\PickedUpState::class)) {
                    $shipment->state->transitionTo(\App\Domains\Shipments\States\PickedUpState::class);
                }
                if ($shipment->state->canTransitionTo(InTransitState::class)) {
                    $shipment->state->transitionTo(InTransitState::class);
                }
            }

            return $route;
        });
    }
}
