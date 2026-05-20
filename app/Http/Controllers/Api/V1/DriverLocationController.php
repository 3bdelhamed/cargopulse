<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Fleet\DTOs\DriverLocationData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class DriverLocationController extends Controller
{
    public function store(DriverLocationData $data): JsonResponse
    {
        // For driver apps, the authenticated user is the driver.
        $driverId = Auth::id(); 
        $tenantId = Auth::user()->tenant_id;

        $geoKey = "tenant:{$tenantId}:drivers:geo";
        $hashKey = "tenant:{$tenantId}:drivers:data";

        // Write coordinates to Redis Geospatial index (no PostgreSQL I/O)
        Redis::geoadd($geoKey, $data->lng, $data->lat, $driverId);

        // Store additional metadata (like timestamp) in a Redis Hash for batch processing
        Redis::hset($hashKey, $driverId, json_encode([
            'lat' => $data->lat,
            'lng' => $data->lng,
            'timestamp' => $data->timestamp,
            'received_at' => now()->toDateTimeString(),
        ]));

        return response()->json([
            'message' => 'Location ingested successfully',
        ], 202);
    }
}
