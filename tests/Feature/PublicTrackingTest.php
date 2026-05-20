<?php

namespace Tests\Feature;

use App\Domains\Shipments\States\PendingState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class PublicTrackingTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_public_tracking_page_loads_with_shipment_data(): void
    {
        $tenant = $this->tenant();
        $merchant = $this->merchantForTenant($tenant);
        $shipment = $this->shipmentForTenant($tenant, $merchant, [
            'tracking_number' => 'CP-TRACK-123',
            'state' => PendingState::$name,
        ]);

        $response = $this->get('/track/CP-TRACK-123');

        $response->assertStatus(200);
        $response->assertViewIs('public.tracking');
        $response->assertViewHas('shipment');
        
        $viewShipment = $response->viewData('shipment');
        $this->assertEquals($shipment->id, $viewShipment->id);
        
        $response->assertViewHas('reverbConfig');
    }
}
