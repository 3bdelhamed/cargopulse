<?php

namespace Tests\Feature\E2E;

use App\Domains\Fleet\Events\DriverLocationUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class RealTimeTrackingE2ETest extends TestCase
{
    use RefreshDatabase, BuildsLogisticsData;

    public function test_driver_location_triggers_reverb_event()
    {
        $tenant = $this->tenant();
        $driverUser = $this->userForTenant($tenant, ['role' => 'Driver']);
        $driver = $this->driverForTenant($tenant, $driverUser);

        Redis::shouldReceive('geoadd')->once()->andReturn(1);
        Redis::shouldReceive('hset')->once()->andReturn(1);

        Event::fake([DriverLocationUpdated::class]);

        $payload = [
            'lat' => 30.0444,
            'lng' => 31.2357,
            'timestamp' => now()->timestamp,
        ];

        $response = $this->actingAs($driverUser)->postJson('/api/v1/drivers/location', $payload);
        $response->assertAccepted();

        Event::assertDispatched(DriverLocationUpdated::class, function ($event) use ($tenant, $driver, $payload) {
            return $event->tenantId === $tenant->id &&
                   $event->driverId === $driver->id &&
                   $event->lat === $payload['lat'] &&
                   $event->lng === $payload['lng'];
        });
    }
}
