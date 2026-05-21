<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\DTOs\ReorderRouteStopsData;
use App\Domains\Fleet\Models\Route;
use App\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

class ReorderRouteStopsAction
{
    public function execute(Route $route, ReorderRouteStopsData $data): Route
    {
        return DB::transaction(function () use ($route, $data) {
            $route = Route::whereKey($route->id)->lockForUpdate()->firstOrFail();

            if (! in_array($route->status, Route::activeStatuses(), true)) {
                throw new DomainRuleException('Only active routes can be reordered.');
            }

            $routeShipmentIds = $route->shipments()->pluck('id')->sort()->values()->all();
            $requestedShipmentIds = collect($data->shipment_ids)->sort()->values()->all();

            if ($routeShipmentIds !== $requestedShipmentIds) {
                throw new DomainRuleException('Route stop order must include exactly the route shipments.');
            }

            foreach ($data->shipment_ids as $sequence => $shipmentId) {
                $route->shipments()->where('id', $shipmentId)->update([
                    'route_sequence' => $sequence + 1,
                ]);
            }

            return $route->load('shipments');
        });
    }
}
