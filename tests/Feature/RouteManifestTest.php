<?php

namespace Tests\Feature;

use App\Domains\Fleet\Actions\CreateRouteManifestAction;
use App\Domains\Fleet\Actions\ReorderRouteStopsAction;
use App\Domains\Fleet\Actions\StartRouteAction;
use App\Domains\Fleet\DTOs\ReorderRouteStopsData;
use App\Domains\Fleet\DTOs\RouteAssignmentData;
use App\Domains\Shipments\States\AssignedState;
use App\Domains\Shipments\States\InTransitState;
use App\Domains\Shipments\States\PackedState;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class RouteManifestTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_route_manifest_assigns_exactly_one_driver_and_sequences_shipments(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $driver = $this->driverForTenant($tenant, $user);
        $merchant = $this->merchantForTenant($tenant);

        $this->actingAs($user);

        $first = $this->shipmentForTenant($tenant, $merchant, ['state' => PackedState::$name]);
        $second = $this->shipmentForTenant($tenant, $merchant, ['state' => PackedState::$name]);

        $route = app(CreateRouteManifestAction::class)->execute(new RouteAssignmentData(
            driver_id: $driver->id,
            dispatch_date: '2026-05-20',
            shipment_ids: [$second->id, $first->id],
        ));

        $this->assertSame('draft', $route->status);
        $this->assertSame($driver->id, $route->driver_id);
        $this->assertSame([$second->id, $first->id], $route->shipments->pluck('id')->all());
        $this->assertSame(1, $second->refresh()->route_sequence);
        $this->assertSame(2, $first->refresh()->route_sequence);
        $this->assertInstanceOf(AssignedState::class, $first->state);

        $this->expectException(Exception::class);

        app(CreateRouteManifestAction::class)->execute(new RouteAssignmentData(
            driver_id: $driver->id,
            dispatch_date: '2026-05-21',
            shipment_ids: [$first->id],
        ));
    }

    public function test_route_start_transitions_assigned_shipments_and_reorder_updates_stops(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $driver = $this->driverForTenant($tenant, $user);
        $merchant = $this->merchantForTenant($tenant);

        $this->actingAs($user);

        $first = $this->shipmentForTenant($tenant, $merchant, ['state' => PackedState::$name]);
        $second = $this->shipmentForTenant($tenant, $merchant, ['state' => PackedState::$name]);

        $route = app(CreateRouteManifestAction::class)->execute(new RouteAssignmentData(
            driver_id: $driver->id,
            dispatch_date: '2026-05-20',
            shipment_ids: [$first->id, $second->id],
        ));

        app(ReorderRouteStopsAction::class)->execute($route, new ReorderRouteStopsData([
            $second->id,
            $first->id,
        ]));

        $startedRoute = app(StartRouteAction::class)->execute($route->refresh());

        $this->assertSame('in_progress', $startedRoute->status);
        $this->assertSame(2, $first->refresh()->route_sequence);
        $this->assertInstanceOf(InTransitState::class, $first->state);
        $this->assertInstanceOf(InTransitState::class, $second->refresh()->state);
    }
}
