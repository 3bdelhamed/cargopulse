<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use App\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

class CompleteRouteAction
{
    public function execute(Route $route): Route
    {
        return DB::transaction(function () use ($route) {
            $route = Route::whereKey($route->id)->lockForUpdate()->firstOrFail();

            if ($route->status !== Route::STATUS_IN_PROGRESS) {
                throw new DomainRuleException('Only in-progress routes can be completed.');
            }

            if (! $this->allShipmentsTerminal($route)) {
                throw new DomainRuleException('Route cannot be completed until all shipments are delivered or failed.');
            }

            $route->update(['status' => Route::STATUS_COMPLETED]);

            return $route->load('shipments');
        });
    }

    public function completeIfReady(Route $route): ?Route
    {
        return DB::transaction(function () use ($route) {
            $route = Route::whereKey($route->id)->lockForUpdate()->first();

            if (! $route || $route->status !== Route::STATUS_IN_PROGRESS || ! $this->allShipmentsTerminal($route)) {
                return null;
            }

            $route->update(['status' => Route::STATUS_COMPLETED]);

            return $route->load('shipments');
        });
    }

    private function allShipmentsTerminal(Route $route): bool
    {
        $shipments = $route->shipments()->get();

        return $shipments->isNotEmpty() && $shipments->every(
            fn ($shipment): bool => $shipment->state instanceof DeliveredState || $shipment->state instanceof FailedState
        );
    }
}
