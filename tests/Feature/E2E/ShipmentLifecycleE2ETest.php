<?php

namespace Tests\Feature\E2E;

use App\Domains\Shipments\Events\ShipmentDeliveredEvent;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Shipments\States\AssignedState;
use App\Domains\Shipments\States\ConfirmedState;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\InTransitState;
use App\Domains\Shipments\States\PackedState;
use App\Domains\Shipments\States\PendingState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class ShipmentLifecycleE2ETest extends TestCase
{
    use RefreshDatabase, BuildsLogisticsData;

    public function test_shipment_completes_full_lifecycle_and_triggers_events()
    {
        $tenant = $this->tenant();
        $merchantUser = $this->userForTenant($tenant, ['role' => 'Merchant', 'email' => 'merchant@test.com']);
        $warehouseUser = $this->userForTenant($tenant, ['role' => 'Warehouse Manager', 'email' => 'wh@test.com']);
        $dispatcherUser = $this->userForTenant($tenant, ['role' => 'Dispatcher', 'email' => 'disp@test.com']);
        $driverUser = $this->userForTenant($tenant, ['role' => 'Driver', 'email' => 'driver@test.com']);
        
        $merchant = $this->merchantForTenant($tenant);
        $driver = $this->driverForTenant($tenant, $driverUser);
        $warehouse = $this->warehouseForTenant($tenant);

        // Step 1: Merchant POST /api/v1/shipments
        $response = $this->actingAs($merchantUser)->postJson('/api/v1/shipments', [
            'merchant_id' => $merchant->id,
            'tracking_number' => 'TRK-E2E-' . rand(1000, 9999),
            'pickup_address' => '123 Origin St',
            'destination_address' => '456 Dest St',
            'cod_amount' => 100,
        ]);
        $response->assertCreated();
        $shipmentId = $response->json('data.id');
        $shipment = Shipment::withoutGlobalScopes()->find($shipmentId);
        $this->assertEquals(PendingState::$name, $shipment->state->getValue());

        // Step 2: Warehouse Manager transitions to ConfirmedState, then Check-In endpoint
        $shipment->state->transitionTo(ConfirmedState::class);
        $this->assertEquals(ConfirmedState::$name, $shipment->state->getValue());

        $response = $this->actingAs($warehouseUser)->postJson('/api/v1/warehouses/check-in', [
            'tracking_number' => $shipment->tracking_number,
            'warehouse_id' => $warehouse->id,
        ]);
        $response->assertOk();
        $this->assertEquals(PackedState::$name, $shipment->fresh()->state->getValue());

        // Step 3: Dispatcher POST /api/v1/routes
        $response = $this->actingAs($dispatcherUser)->postJson('/api/v1/routes', [
            'driver_id' => $driver->id,
            'dispatch_date' => now()->toDateString(),
            'shipment_ids' => [$shipment->id],
        ]);
        $response->assertCreated();
        $routeId = $response->json('data.id');
        $this->assertEquals(AssignedState::$name, $shipment->fresh()->state->getValue());

        // Step 4: Start route to PickedUp and InTransit
        $response = $this->actingAs($driverUser)->postJson("/api/v1/routes/{$routeId}/start");
        $response->assertOk();
        $this->assertEquals(InTransitState::$name, $shipment->fresh()->state->getValue());

        // Step 5: Fake event, manually transition to Delivered
        Event::fake([ShipmentDeliveredEvent::class]);
        $shipment->fresh()->state->transitionTo(DeliveredState::class);

        $this->assertEquals(DeliveredState::$name, $shipment->fresh()->state->getValue());
        Event::assertDispatched(ShipmentDeliveredEvent::class);
    }
}
