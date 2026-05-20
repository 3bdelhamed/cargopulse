<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\DTOs\RouteAssignmentData;
use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Shipments\States\AssignedState;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateRouteManifestAction
{
    public function execute(RouteAssignmentData $data): Route
    {
        return DB::transaction(function () use ($data) {
            // Validate strict cardinality: A driver can only have one active route
            $activeRoute = Route::where('driver_id', $data->driver_id)
                ->whereIn('status', ['draft', 'in_progress'])
                ->first();

            if ($activeRoute) {
                throw new Exception("Driver already has an active or draft route.");
            }

            $route = Route::create([
                'driver_id' => $data->driver_id,
                'name' => 'Manifest - ' . $data->dispatch_date,
                'date' => $data->dispatch_date,
                'status' => 'draft',
            ]);

            // Assign shipments and persist specific drop-off sequence
            foreach ($data->shipment_ids as $sequence => $shipmentId) {
                $shipment = Shipment::lockForUpdate()->findOrFail($shipmentId);
                
                $shipment->update([
                    'route_id' => $route->id,
                    'driver_id' => $data->driver_id,
                    'route_sequence' => $sequence + 1,
                ]);

                if ($shipment->state->canTransitionTo(AssignedState::class)) {
                    $shipment->state->transitionTo(AssignedState::class);
                }
            }

            return $route->load('shipments');
        });
    }
}
