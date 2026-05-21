<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\DTOs\RouteAssignmentData;
use App\Domains\Fleet\Models\Driver;
use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Shipments\States\AssignedState;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

class CreateRouteManifestAction
{
    public function execute(RouteAssignmentData $data): Route
    {
        return DB::transaction(function () use ($data) {
            Driver::whereKey($data->driver_id)->lockForUpdate()->firstOrFail();

            $activeRoute = Route::where('driver_id', $data->driver_id)
                ->whereIn('status', Route::activeStatuses())
                ->lockForUpdate()
                ->first();

            if ($activeRoute) {
                throw new ConflictException('Driver already has an active route.');
            }

            $route = Route::create([
                'driver_id' => $data->driver_id,
                'name' => 'Manifest - ' . $data->dispatch_date,
                'date' => $data->dispatch_date,
                'status' => Route::STATUS_DRAFT,
            ]);

            $shipments = Shipment::with('route')
                ->whereKey($data->shipment_ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($shipments->count() !== count($data->shipment_ids)) {
                throw new DomainRuleException('One or more shipments are not available for this tenant.');
            }

            foreach ($data->shipment_ids as $sequence => $shipmentId) {
                $shipment = $shipments->get($shipmentId);

                if ($shipment->route && in_array($shipment->route->status, Route::activeStatuses(), true)) {
                    throw new ConflictException('Shipment is already assigned to an active route.');
                }

                if (! $shipment->state->canTransitionTo(AssignedState::class)) {
                    throw new DomainRuleException('Shipment is not in an assignable state.');
                }

                $shipment->update([
                    'route_id' => $route->id,
                    'driver_id' => $data->driver_id,
                    'route_sequence' => $sequence + 1,
                ]);

                $shipment->state->transitionTo(AssignedState::class);
            }

            return $route->load('shipments');
        });
    }
}
