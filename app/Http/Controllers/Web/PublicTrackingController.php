<?php

namespace App\Http\Controllers\Web;

use App\Domains\Shipments\Models\Shipment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class PublicTrackingController extends Controller
{
    public function show(string $trackingNumber)
    {
        // Fetch the shipment without tenant scope since it's a public route
        $shipment = Shipment::withoutGlobalScopes()->where('tracking_number', $trackingNumber)->firstOrFail();

        // Attempt to fetch the driver's current live location directly from Redis
        $redisData = \Illuminate\Support\Facades\Redis::geopos("tenant:{$shipment->tenant_id}:drivers:geo", $shipment->driver_id);
        $liveLocation = (!empty($redisData) && is_array($redisData[0]) && count($redisData[0]) >= 2) 
            ? ['lat' => $redisData[0][1], 'lng' => $redisData[0][0]] 
            : null;

        return view('public.tracking', [
            'shipment' => $shipment,
            'liveLocation' => $liveLocation,
            'reverbConfig' => config('broadcasting.connections.reverb'),
        ]);
    }
}
