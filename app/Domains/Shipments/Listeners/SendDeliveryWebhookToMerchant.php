<?php

namespace App\Domains\Shipments\Listeners;

use App\Domains\Shipments\Events\ShipmentDeliveredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendDeliveryWebhookToMerchant implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    
    // Time (in seconds) to wait before retrying a failed job
    public int $backoff = 30;

    public function handle(ShipmentDeliveredEvent $event): void
    {
        $shipment = $event->shipment;
        
        $merchant = DB::table('merchants')->where('id', $shipment->merchant_id)->first();

        if (!$merchant || empty($merchant->webhook_url)) {
            return;
        }

        $payload = [
            'event' => 'shipment.delivered',
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'delivered_at' => now()->toIso8601String(),
        ];

        $response = Http::timeout(5)->post($merchant->webhook_url, $payload);

        if ($response->failed()) {
            Log::warning("Failed to deliver webhook for shipment {$shipment->id}", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            
            // Re-queue the job using the configured backoff
            $this->release($this->backoff);
        }
    }
}
