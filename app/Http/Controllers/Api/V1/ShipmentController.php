<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Shipments\Actions\CreateShipmentAction;
use App\Domains\Shipments\DTOs\ShipmentData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ShipmentController extends Controller
{
    public function store(ShipmentData $data, CreateShipmentAction $action): JsonResponse
    {
        $shipment = $action->execute($data);

        return response()->json([
            'message' => 'Shipment created successfully',
            'data' => $shipment,
        ], 201);
    }
}
