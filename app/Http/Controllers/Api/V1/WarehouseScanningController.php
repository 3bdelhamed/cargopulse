<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Warehouses\Actions\CheckInShipmentAction;
use App\Domains\Warehouses\Actions\TransferShipmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WarehouseScanRequest;
use Illuminate\Http\JsonResponse;

class WarehouseScanningController extends Controller
{
    /**
     * Highly optimized endpoint for initial check-in scanning.
     */
    public function checkIn(WarehouseScanRequest $request, CheckInShipmentAction $action): JsonResponse
    {
        $shipment = $action->execute($request->toData());

        return response()->json([
            'message' => 'Shipment checked into warehouse successfully',
            'data' => [
                'id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'state' => $shipment->state->getValue(),
                'warehouse_id' => $shipment->warehouse_id,
            ],
        ]);
    }

    /**
     * Endpoint for internal warehouse transfers.
     */
    public function transfer(WarehouseScanRequest $request, TransferShipmentAction $action): JsonResponse
    {
        $shipment = $action->execute($request->toData());

        return response()->json([
            'message' => 'Shipment transferred successfully',
            'data' => [
                'id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'state' => $shipment->state->getValue(),
                'warehouse_id' => $shipment->warehouse_id,
            ],
        ]);
    }
}
