<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class DriverLocationIngestionTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_driver_location_endpoint_writes_gps_data_to_redis(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant, ['role' => 'Driver']);

        Sanctum::actingAs($user, ['gps:update']);

        Redis::shouldReceive('geoadd')
            ->once()
            ->with("tenant:{$tenant->id}:drivers:geo", 31.2357, 30.0444, $user->id);

        Redis::shouldReceive('hset')
            ->once()
            ->withArgs(function (string $key, int $driverId, string $payload) use ($tenant, $user) {
                $data = json_decode($payload, true);

                return $key === "tenant:{$tenant->id}:drivers:data"
                    && $driverId === $user->id
                    && $data['lat'] === 30.0444
                    && $data['lng'] === 31.2357
                    && $data['timestamp'] === 1779254400;
            });

        $response = $this->postJson('/api/v1/drivers/location', [
            'lat' => 30.0444,
            'lng' => 31.2357,
            'timestamp' => 1779254400,
        ]);

        $response->assertAccepted()
            ->assertJsonPath('message', 'Location ingested successfully');
    }
}
