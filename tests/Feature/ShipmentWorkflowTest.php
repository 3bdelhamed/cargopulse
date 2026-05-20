<?php

namespace Tests\Feature;

use App\Domains\Shipments\Actions\CreateShipmentAction;
use App\Domains\Shipments\DTOs\ShipmentData;
use App\Domains\Shipments\Events\ShipmentDeliveredEvent;
use App\Domains\Shipments\States\AssignedState;
use App\Domains\Shipments\States\ConfirmedState;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\InTransitState;
use App\Domains\Shipments\States\PackedState;
use App\Domains\Shipments\States\PendingState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class ShipmentWorkflowTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_create_shipment_action_starts_pending_and_preserves_cod_amount(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);

        $this->actingAs($user);

        $shipment = app(CreateShipmentAction::class)->execute(new ShipmentData(
            merchant_id: $merchant->id,
            tracking_number: 'CP-1001',
            destination_address: '42 Customer Street',
            cod_amount: 125.50,
        ));

        $this->assertSame($tenant->id, $shipment->tenant_id);
        $this->assertInstanceOf(PendingState::class, $shipment->state);
        $this->assertSame(125.5, (float) $shipment->cod_amount);
    }

    public function test_shipment_state_machine_blocks_invalid_jumps_and_dispatches_delivered_event(): void
    {
        Event::fake([ShipmentDeliveredEvent::class]);

        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);

        $this->actingAs($user);

        $shipment = $this->shipmentForTenant($tenant, $merchant, [
            'state' => PendingState::$name,
        ]);

        $this->expectException(TransitionNotFound::class);
        $shipment->state->transitionTo(DeliveredState::class);
    }

    public function test_shipment_state_machine_allows_prd_lifecycle_to_delivery(): void
    {
        Event::fake([ShipmentDeliveredEvent::class]);

        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);

        $this->actingAs($user);

        $shipment = $this->shipmentForTenant($tenant, $merchant, [
            'state' => PendingState::$name,
        ]);

        $shipment->state->transitionTo(ConfirmedState::class);
        $shipment->state->transitionTo(PackedState::class);
        $shipment->state->transitionTo(AssignedState::class);
        $shipment->state->transitionTo(InTransitState::class);
        $shipment->state->transitionTo(DeliveredState::class);

        $this->assertInstanceOf(DeliveredState::class, $shipment->refresh()->state);
        Event::assertDispatched(ShipmentDeliveredEvent::class);
    }
}
