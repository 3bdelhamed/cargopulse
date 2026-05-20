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
        $liveLocation = Redis::geopos("tenant:{$shipment->tenant_id}:drivers:geo", $shipment->driver_id);
        
        $lat = null;
        $lng = null;
        if (!empty($liveLocation) && isset($liveLocation[0])) {
            $lng = $liveLocation[0][0]; // geopos returns lng, lat
            $lat = $liveLocation[0][1];
        }

        return view('public.tracking', [
            'shipment' => $shipment,
            'lat' => $lat,
            'lng' => $lng,
            'reverbConfig' => config('broadcasting.connections.reverb'),
        ]);
    }
}
