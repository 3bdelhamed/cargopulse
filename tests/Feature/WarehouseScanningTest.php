<?php

namespace Tests\Feature;

use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\States\ConfirmedState;
use App\Domains\Shipments\States\PackedState;
use App\Domains\Warehouses\Actions\CheckInShipmentAction;
use App\Domains\Warehouses\Actions\TransferShipmentAction;
use App\Domains\Warehouses\DTOs\ScanData;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class WarehouseScanningTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_check_in_locks_shipment_to_warehouse_clears_route_and_audits_status(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $driver = $this->driverForTenant($tenant, $user);
        $merchant = $this->merchantForTenant($tenant);
        $warehouse = $this->warehouseForTenant($tenant);

        $this->actingAs($user);

        $route = Route::create([
            'driver_id' => $driver->id,
            'name' => 'Morning Manifest',
            'date' => '2026-05-20',
            'status' => 'draft',
        ]);

        $shipment = $this->shipmentForTenant($tenant, $merchant, [
            'state' => ConfirmedState::$name,
            'driver_id' => $driver->id,
            'route_id' => $route->id,
            'route_sequence' => 3,
        ]);

        $checkedIn = app(CheckInShipmentAction::class)->execute(new ScanData(
            tracking_number: $shipment->tracking_number,
            warehouse_id: $warehouse->id,
        ));

        $this->assertSame($warehouse->id, $checkedIn->warehouse_id);
        $this->assertNull($checkedIn->route_id);
        $this->assertNull($checkedIn->driver_id);
        $this->assertNull($checkedIn->route_sequence);
        $this->assertInstanceOf(PackedState::class, $checkedIn->state);
        $this->assertDatabaseHas('shipment_status_logs', [
            'tenant_id' => $tenant->id,
            'shipment_id' => $shipment->id,
            'from_state' => ConfirmedState::$name,
            'to_state' => PackedState::$name,
            'user_id' => $user->id,
        ]);
    }

    public function test_transfer_moves_between_warehouses_and_rejects_same_location(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);
        $origin = $this->warehouseForTenant($tenant, ['code' => 'A']);
        $destination = $this->warehouseForTenant($tenant, ['code' => 'B']);

        $this->actingAs($user);

        $shipment = $this->shipmentForTenant($tenant, $merchant, [
            'warehouse_id' => $origin->id,
        ]);

        $transferred = app(TransferShipmentAction::class)->execute(new ScanData(
            tracking_number: $shipment->tracking_number,
            warehouse_id: $destination->id,
        ));

        $this->assertSame($destination->id, $transferred->warehouse_id);
        $this->assertDatabaseHas('shipment_status_logs', [
            'shipment_id' => $shipment->id,
            'from_state' => $shipment->state->getValue(),
            'to_state' => $shipment->state->getValue(),
        ]);

        $this->expectException(Exception::class);

        app(TransferShipmentAction::class)->execute(new ScanData(
            tracking_number: $shipment->tracking_number,
            warehouse_id: $destination->id,
        ));
    }
}
