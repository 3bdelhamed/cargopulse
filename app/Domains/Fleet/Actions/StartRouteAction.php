<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\States\InTransitState;
use Exception;
use Illuminate\Support\Facades\DB;

class StartRouteAction
{
    public function execute(Route $route): Route
    {
        if ($route->status === 'in_progress') {
            throw new Exception("Route is already in progress.");
        }

        return DB::transaction(function () use ($route) {
            $route->update(['status' => 'in_progress']);

            // Automatically transition all assigned shipments
            foreach ($route->shipments as $shipment) {
                if ($shipment->state->canTransitionTo(InTransitState::class)) {
                    $shipment->state->transitionTo(InTransitState::class);
                }
            }

            return $route;
        });
    }
}
