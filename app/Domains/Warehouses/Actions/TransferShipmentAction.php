<?php

namespace App\Domains\Warehouses\Actions;

use App\Domains\Shipments\Models\Shipment;
use App\Domains\Warehouses\DTOs\ScanData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class TransferShipmentAction
{
    /**
     * Handles warehouse-to-warehouse transfers efficiently.
     */
    public function execute(ScanData $data): Shipment
    {
        return DB::transaction(function () use ($data) {
            $shipment = Shipment::lockForUpdate()
                ->where('tracking_number', $data->tracking_number)
                ->firstOrFail();

            if ($shipment->warehouse_id === $data->warehouse_id) {
                throw new Exception("Shipment is already located in this warehouse.");
            }

            $oldWarehouseId = $shipment->warehouse_id;

            $shipment->update([
                'warehouse_id' => $data->warehouse_id,
                'route_id' => null,
                'driver_id' => null,
            ]);

            // Maintain audit log specifically for internal node transfers without a strict state transition
            DB::table('shipment_status_logs')->insert([
                'tenant_id' => $shipment->tenant_id,
                'shipment_id' => $shipment->id,
                'from_state' => $shipment->state->getValue(),
                'to_state' => $shipment->state->getValue(),
                'user_id' => Auth::id(),
                'reason' => "Internal Transfer: Moved from Warehouse #" . ($oldWarehouseId ?? 'Unknown') . " to #{$data->warehouse_id}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $shipment;
        });
    }
}
