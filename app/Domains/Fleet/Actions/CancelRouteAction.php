<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use App\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

class CancelRouteAction
{
    public function execute(Route $route): Route
    {
        return DB::transaction(function () use ($route) {
            $route = Route::whereKey($route->id)->lockForUpdate()->firstOrFail();

            if (! in_array($route->status, Route::activeStatuses(), true)) {
                throw new DomainRuleException('Only active routes can be cancelled.');
            }

            foreach ($route->shipments()->lockForUpdate()->get() as $shipment) {
                if ($shipment->state instanceof DeliveredState || $shipment->state instanceof FailedState) {
                    continue;
                }

                $shipment->update([
                    'route_id' => null,
                    'driver_id' => null,
                    'route_sequence' => null,
                ]);
            }

            $route->update(['status' => Route::STATUS_CANCELLED]);

            return $route->load('shipments');
        });
    }
}
