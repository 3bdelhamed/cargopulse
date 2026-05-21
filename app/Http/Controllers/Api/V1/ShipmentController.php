<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Shipments\Actions\CreateShipmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreShipmentRequest;
use App\Http\Resources\Api\V1\ShipmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ShipmentController extends Controller
{
    public function store(StoreShipmentRequest $request, CreateShipmentAction $action): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        $cacheKey = $idempotencyKey
            ? 'idempotency:shipments:'.$request->user()->tenant_id.':'.hash('sha256', $idempotencyKey)
            : null;

        if ($cacheKey && Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey), 201);
        }

        $shipment = $action->execute($request->toData());

        $payload = [
            'message' => 'Shipment created successfully',
            'data' => (new ShipmentResource($shipment))->resolve($request),
        ];

        if ($cacheKey) {
            Cache::put($cacheKey, $payload, now()->addDay());
        }

        return response()->json($payload, 201);
    }
}
