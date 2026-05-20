<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\DTOs\ReorderRouteStopsData;
use App\Domains\Fleet\Models\Route;
use Illuminate\Support\Facades\DB;

class ReorderRouteStopsAction
{
    public function execute(Route $route, ReorderRouteStopsData $data): Route
    {
        return DB::transaction(function () use ($route, $data) {
            foreach ($data->shipment_ids as $sequence => $shipmentId) {
                // Update specific stop sequence order securely inside transaction
                $route->shipments()->where('id', $shipmentId)->update([
                    'route_sequence' => $sequence + 1, // 1-based index
                ]);
            }
            return $route->load('shipments');
        });
    }
}
