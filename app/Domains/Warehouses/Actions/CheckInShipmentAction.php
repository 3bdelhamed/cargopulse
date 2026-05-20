<?php

namespace App\Domains\Warehouses\Actions;

use App\Domains\Shipments\Models\Shipment;
use App\Domains\Shipments\States\PackedState;
use App\Domains\Warehouses\DTOs\ScanData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckInShipmentAction
{
    public function execute(ScanData $data): Shipment
    {
        return DB::transaction(function () use ($data) {
            // Find shipment quickly utilizing tracking_number tenant_id index
            $shipment = Shipment::lockForUpdate()
                ->where('tracking_number', $data->tracking_number)
                ->firstOrFail();

            $fromState = $shipment->state->getValue();

            // 1. Unassign from any routes/drivers and lock into the warehouse zone
            $shipment->update([
                'warehouse_id' => $data->warehouse_id,
                'route_id' => null,
                'driver_id' => null,
                'route_sequence' => null,
            ]);

            // 2. Transition state to PackedState securely mapping logistics flow
            if ($shipment->state->canTransitionTo(PackedState::class)) {
                $shipment->state->transitionTo(PackedState::class);
            }

            // 3. Perfect paper trail for check-in via Audit Log
            DB::table('shipment_status_logs')->insert([
                'tenant_id' => $shipment->tenant_id,
                'shipment_id' => $shipment->id,
                'from_state' => $fromState,
                'to_state' => $shipment->state->getValue(),
                'user_id' => Auth::id(),
                'reason' => "Scanned and checked into Warehouse #{$data->warehouse_id}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $shipment;
        });
    }
}
