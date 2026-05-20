<?php

namespace Tests\Feature;

use App\Domains\Analytics\Actions\GetTenantDashboardMetricsAction;
use App\Domains\Fleet\Models\Route;
use App\Domains\Shipments\Queries\AdvancedShipmentSearch;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use App\Domains\Shipments\States\InTransitState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class AnalyticsAndSearchTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_dashboard_metrics_are_tenant_scoped(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant(['name' => 'Other']);
        $user = $this->userForTenant($tenant);
        $driver = $this->driverForTenant($tenant, $user);
        $merchant = $this->merchantForTenant($tenant);
        $otherMerchant = $this->merchantForTenant($otherTenant);

        $this->shipmentForTenant($tenant, $merchant, [
            'state' => DeliveredState::$name,
            'created_at' => Carbon::parse('2026-05-20 08:00:00'),
            'updated_at' => Carbon::parse('2026-05-20 12:00:00'),
        ]);
        $this->shipmentForTenant($tenant, $merchant, ['state' => FailedState::$name]);
        $this->shipmentForTenant($otherTenant, $otherMerchant, ['state' => DeliveredState::$name]);

        $this->actingAs($user);

        Route::create([
            'driver_id' => $driver->id,
            'name' => 'Active Manifest',
            'date' => '2026-05-20',
            'status' => 'in_progress',
        ]);

        $metrics = app(GetTenantDashboardMetricsAction::class)->execute();

        $this->assertSame(50.0, $metrics->delivery_success_rate);
        $this->assertSame(4.0, $metrics->average_delivery_duration_hours);
        $this->assertSame(50.0, $metrics->failed_to_returned_ratio);
        $this->assertSame(1, $metrics->active_drivers_count);
    }

    public function test_advanced_search_filters_and_sorts_shipments(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);

        $this->actingAs($user);

        $first = $this->shipmentForTenant($tenant, $merchant, [
            'tracking_number' => 'CP-SEARCH-001',
            'state' => DeliveredState::$name,
            'delivery_fee' => 20,
        ]);
        $this->shipmentForTenant($tenant, $merchant, [
            'tracking_number' => 'CP-SEARCH-002',
            'state' => InTransitState::$name,
            'delivery_fee' => 10,
        ]);

        $results = app(AdvancedShipmentSearch::class)->execute([
            'state' => DeliveredState::$name,
            'tracking_number' => 'search',
            'sort_by' => 'delivery_fee',
            'sort_direction' => 'asc',
        ])->get();

        $this->assertCount(1, $results);
        $this->assertTrue($first->is($results->first()));
    }
}
