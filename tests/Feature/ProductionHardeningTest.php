<?php

namespace Tests\Feature;

use App\Domains\Fleet\Actions\CreateRouteManifestAction;
use App\Domains\Fleet\Actions\StartRouteAction;
use App\Domains\Fleet\DTOs\RouteAssignmentData;
use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use App\Domains\Shipments\States\PackedState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_duplicate_tracking_number_returns_safe_validation_response(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);

        $this->shipmentForTenant($tenant, $merchant, [
            'tracking_number' => 'CP-DUP-001',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/shipments', [
            'merchant_id' => $merchant->id,
            'tracking_number' => 'CP-DUP-001',
            'destination_address' => 'Customer address',
            'pickup_address' => 'Origin hub',
            'cod_amount' => 10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonPath('errors.tracking_number.0', 'Tracking number already exists.');

        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertStringNotContainsString('vendor', $response->getContent());
    }

    public function test_tracking_number_uniqueness_is_scoped_per_tenant(): void
    {
        $firstTenant = $this->tenant(['name' => 'Tenant A']);
        $secondTenant = $this->tenant(['name' => 'Tenant B']);
        $firstMerchant = $this->merchantForTenant($firstTenant);
        $secondMerchant = $this->merchantForTenant($secondTenant);
        $user = $this->userForTenant($secondTenant);

        $this->shipmentForTenant($firstTenant, $firstMerchant, [
            'tracking_number' => 'CP-SHARED-001',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/shipments', [
            'merchant_id' => $secondMerchant->id,
            'tracking_number' => 'CP-SHARED-001',
            'destination_address' => 'Customer address',
            'pickup_address' => 'Origin hub',
            'cod_amount' => 10,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.tracking_number', 'CP-SHARED-001')
            ->assertJsonMissingPath('data.tenant_id');

        $this->assertDatabaseHas('shipments', [
            'tenant_id' => $secondTenant->id,
            'tracking_number' => 'CP-SHARED-001',
        ]);
    }

    public function test_shipment_creation_rejects_cross_tenant_merchant(): void
    {
        $tenant = $this->tenant(['name' => 'Tenant A']);
        $otherTenant = $this->tenant(['name' => 'Tenant B']);
        $user = $this->userForTenant($tenant);
        $otherMerchant = $this->merchantForTenant($otherTenant);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/shipments', [
            'merchant_id' => $otherMerchant->id,
            'tracking_number' => 'CP-CROSS-001',
            'destination_address' => 'Customer address',
            'pickup_address' => 'Origin hub',
            'cod_amount' => 10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.merchant_id.0', 'Merchant does not exist for this tenant.');
    }

    public function test_search_rejects_invalid_sort_and_state_values(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/shipments/search?state=delivered;drop&sort_by=tenant_id&per_page=1000');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['state', 'sort_by', 'per_page']);
    }

    public function test_route_assignment_rejects_shipments_already_on_active_routes(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $driver = $this->driverForTenant($tenant, $user);
        $otherDriver = $this->driverForTenant($tenant, $user, ['status' => 'available']);
        $merchant = $this->merchantForTenant($tenant);
        $shipment = $this->shipmentForTenant($tenant, $merchant, ['state' => PackedState::$name]);

        $this->actingAs($user);
        Sanctum::actingAs($user, ['*']);

        app(CreateRouteManifestAction::class)->execute(new RouteAssignmentData(
            driver_id: $driver->id,
            dispatch_date: '2026-05-20',
            shipment_ids: [$shipment->id],
        ));

        $response = $this->postJson('/api/v1/routes', [
            'driver_id' => $otherDriver->id,
            'dispatch_date' => '2026-05-21',
            'shipment_ids' => [$shipment->id],
        ]);

        $response->assertConflict()
            ->assertJsonPath('message', 'Shipment is already assigned to an active route.');
    }

    public function test_route_auto_completes_when_all_shipments_reach_terminal_states(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $driver = $this->driverForTenant($tenant, $user);
        $merchant = $this->merchantForTenant($tenant);
        $first = $this->shipmentForTenant($tenant, $merchant, ['state' => PackedState::$name]);
        $second = $this->shipmentForTenant($tenant, $merchant, ['state' => PackedState::$name]);

        $this->actingAs($user);

        $route = app(CreateRouteManifestAction::class)->execute(new RouteAssignmentData(
            driver_id: $driver->id,
            dispatch_date: '2026-05-20',
            shipment_ids: [$first->id, $second->id],
        ));

        app(StartRouteAction::class)->execute($route);

        $first->refresh()->state->transitionTo(DeliveredState::class);
        $this->assertSame(Route::STATUS_IN_PROGRESS, $route->refresh()->status);

        $second->refresh()->state->transitionTo(FailedState::class);
        $this->assertSame(Route::STATUS_COMPLETED, $route->refresh()->status);
    }
}
